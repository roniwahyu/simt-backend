<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FinanceModuleTest — Pengujian modul Finance (Sprint 5 Phase 1)
 *
 * Mencakup:
 * 1. CRUD Tagihan (generate, view, filter)
 * 2. Pembayaran (partial, full, auto-update status)
 * 3. Kwitansi PDF generation
 * 4. Tenant isolation
 * 5. Module gating (Finance non-aktif = 403)
 * 6. API portal ortu endpoint
 */
class FinanceModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $admin;
    protected User $bendahara;
    protected User $wali;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Http::fake();

        // Seed roles & permissions
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        // Setup 2 tenant + modul
        $this->tenant1 = Tenant::create([
            'name' => 'MTs Test Satu',
            'domain' => 'test-satu',
            'status' => 'active',
            'activated_at' => now(),
        ]);
        $this->tenant2 = Tenant::create([
            'name' => 'MTs Test Dua',
            'domain' => 'test-dua',
            'status' => 'active',
            'activated_at' => now(),
        ]);

        // Tenant 1 punya semua modul
        foreach (['Core', 'Student', 'Attendance', 'Finance'] as $mod) {
            \App\Models\TenantModule::create([
                'tenant_id' => $this->tenant1->id,
                'module_code' => $mod,
                'active' => true,
            ]);
        }
        // Tenant 2 tanpa Finance (test module gating)
        foreach (['Core', 'Student', 'Attendance'] as $mod) {
            \App\Models\TenantModule::create([
                'tenant_id' => $this->tenant2->id,
                'module_code' => $mod,
                'active' => true,
            ]);
        }

        // Provision role
        $roleService = new TenantRoleService();
        $roleService->provisionForTenant($this->tenant1->id);
        $roleService->provisionForTenant($this->tenant2->id);

        // Setup users
        $this->admin = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Admin Test',
            'phone' => '628111111001',
            'email' => 'admin@test-satu.sch.id',
            'password' => bcrypt('password'),
            'role_display' => 'admin_sekolah',
            'is_active' => true,
        ]);
        $roleService->assignRole($this->admin, 'admin_sekolah', $this->tenant1->id);

        $this->bendahara = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Bendahara Test',
            'phone' => '628111111002',
            'email' => 'bendahara@test-satu.sch.id',
            'password' => bcrypt('password'),
            'role_display' => 'bendahara',
            'is_active' => true,
        ]);
        $roleService->assignRole($this->bendahara, 'bendahara', $this->tenant1->id);

        $this->wali = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Wali Test',
            'phone' => '628111111003',
            'email' => 'wali@test-satu.sch.id',
            'phone' => '628000000001',
            'password' => bcrypt('password'),
            'role_display' => 'wali',
            'is_active' => true,
        ]);
        $roleService->assignRole($this->wali, 'wali', $this->tenant1->id);

        // Setup SchoolYear
        SchoolYear::create([
            'tenant_id' => $this->tenant1->id,
            'name' => '2026/2027',
            'is_active' => true,
        ]);

        // Setup student + guardian relation
        $this->student = Student::create([
            'tenant_id' => $this->tenant1->id,
            'nis' => '001',
            'nisn' => '001001',
            'name' => 'Siswa Test',
            'status' => 'active',
        ]);
        $this->wali->guardianStudents()->attach($this->student->id, ['relation' => 'ayah']);
    }

    /** @test */
    public function bendahara_can_generate_bills_for_active_students(): void
    {
        $this->actingAs($this->bendahara);

        $response = $this->post(route('finance.bills.generate'), [
            'period' => '2026-07',
            'component' => 'SPP',
            'amount' => 500000,
        ]);

        $response->assertRedirect(route('finance.bills'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bills', [
            'tenant_id' => $this->tenant1->id,
            'student_id' => $this->student->id,
            'period' => '2026-07',
            'component' => 'SPP',
            'amount' => 500000,
            'status' => 'unpaid',
        ]);
    }

    /** @test */
    public function bills_index_page_is_accessible_with_filter(): void
    {
        // Create a bill first
        Bill::create([
            'tenant_id' => $this->tenant1->id,
            'student_id' => $this->student->id,
            'period' => '2026-07',
            'component' => 'SPP',
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        $this->actingAs($this->bendahara);

        $response = $this->get(route('finance.bills'));
        $response->assertOk();
        $response->assertSee('Tagihan SPP');

        // Test filter
        $response = $this->get(route('finance.bills', ['status' => 'paid']));
        $response->assertOk();
    }

    /** @test */
    public function full_payment_updates_bill_status_to_paid(): void
    {
        $bill = Bill::create([
            'tenant_id' => $this->tenant1->id,
            'student_id' => $this->student->id,
            'period' => '2026-07',
            'component' => 'SPP',
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        $this->actingAs($this->bendahara);

        $response = $this->post(route('finance.payment.store', $bill), [
            'amount' => 500000,
            'payment_date' => '2026-07-15',
            'method' => 'cash',
        ]);

        $response->assertRedirect(route('finance.bills'));

        $bill->refresh();
        $this->assertEquals(500000, (float) $bill->paid_amount);
        $this->assertEquals('paid', $bill->status);

        $this->assertDatabaseHas('payments', [
            'tenant_id' => $this->tenant1->id,
            'bill_id' => $bill->id,
            'amount' => 500000,
            'method' => 'cash',
        ]);
    }

    /** @test */
    public function partial_payment_updates_bill_status_to_partial(): void
    {
        $bill = Bill::create([
            'tenant_id' => $this->tenant1->id,
            'student_id' => $this->student->id,
            'period' => '2026-07',
            'component' => 'SPP',
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        $this->actingAs($this->bendahara);

        $response = $this->post(route('finance.payment.store', $bill), [
            'amount' => 200000,
            'payment_date' => '2026-07-15',
            'method' => 'transfer',
        ]);

        $response->assertRedirect(route('finance.bills'));

        $bill->refresh();
        $this->assertEquals(200000, (float) $bill->paid_amount);
        $this->assertEquals('partial', $bill->status);
        $this->assertEquals(300000, $bill->remaining());
    }

    /** @test */
    public function receipt_pdf_generated_with_correct_format(): void
    {
        $bill = Bill::create([
            'tenant_id' => $this->tenant1->id,
            'student_id' => $this->student->id,
            'period' => '2026-07',
            'component' => 'SPP',
            'amount' => 500000,
            'paid_amount' => 500000,
            'status' => 'paid',
        ]);

        $payment = Payment::create([
            'tenant_id' => $this->tenant1->id,
            'bill_id' => $bill->id,
            'student_id' => $this->student->id,
            'amount' => 500000,
            'payment_date' => '2026-07-15',
            'method' => 'cash',
            'receipt_no' => 'KW' . $this->tenant1->id . '-2026-001',
            'recorded_by' => $this->bendahara->id,
        ]);

        $this->actingAs($this->bendahara);

        $response = $this->get(route('finance.receipt', $payment));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function finance_module_disabled_returns_403(): void
    {
        // Tenant 2 tidak punya modul Finance
        $adminT2 = User::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Admin T2',
            'phone' => '628222222001',
            'email' => 'admin@test-dua.sch.id',
            'password' => bcrypt('password'),
            'role_display' => 'admin_sekolah',
            'is_active' => true,
        ]);
        $roleService = new TenantRoleService();
        $roleService->assignRole($adminT2, 'admin_sekolah', $this->tenant2->id);

        $this->actingAs($adminT2);

        $response = $this->get(route('finance.bills'));
        $response->assertStatus(403);
    }

    /** @test */
    public function bills_isolated_per_tenant(): void
    {
        // Setup tenant 2
        $studentT2 = Student::create([
            'tenant_id' => $this->tenant2->id,
            'nis' => '001',
            'nisn' => '001001',
            'name' => 'Siswa T2',
            'status' => 'active',
        ]);

        // Bills di 2 tenant
        Bill::create([
            'tenant_id' => $this->tenant1->id,
            'student_id' => $this->student->id,
            'period' => '2026-07',
            'component' => 'SPP',
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);
        Bill::create([
            'tenant_id' => $this->tenant2->id,
            'student_id' => $studentT2->id,
            'period' => '2026-07',
            'component' => 'SPP',
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        // Login sebagai admin T1
        $this->actingAs($this->admin);
        $response = $this->get(route('finance.bills'));
        $response->assertOk();
        $response->assertSee('Siswa Test');
        $response->assertDontSee('Siswa T2');
    }

    /** @test */
    public function wali_can_only_access_their_own_childs_bills_via_api(): void
    {
        // Setup student lain (bukan anak wali)
        $studentLain = Student::create([
            'tenant_id' => $this->tenant1->id,
            'nis' => '002',
            'nisn' => '001002',
            'name' => 'Siswa Lain',
            'status' => 'active',
        ]);
        Bill::create([
            'tenant_id' => $this->tenant1->id,
            'student_id' => $studentLain->id,
            'period' => '2026-07',
            'component' => 'SPP',
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);
        Bill::create([
            'tenant_id' => $this->tenant1->id,
            'student_id' => $this->student->id,
            'period' => '2026-07',
            'component' => 'SPP',
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        $token = $this->wali->createToken('test-token')->plainTextToken;
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'X-Tenant-Domain' => 'test-satu',
            'Accept' => 'application/json',
        ];

        // Wali coba akses data anak sendiri — SHOULD PASS
        $response = $this->get('/api/v1/students/' . $this->student->id . '/bills', $headers);
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'student' => ['id' => $this->student->id],
            ],
        ]);

        // Wali coba akses data anak orang lain — SHOULD FAIL (403)
        $response = $this->get('/api/v1/students/' . $studentLain->id . '/bills', $headers);
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'code' => 'FORBIDDEN_OWNERSHIP',
        ]);
    }

    /** @test */
    public function bendahara_can_export_bills_to_excel(): void
    {
        Bill::create([
            'tenant_id' => $this->tenant1->id,
            'student_id' => $this->student->id,
            'period' => '2026-07',
            'component' => 'SPP',
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        $this->actingAs($this->bendahara);

        $response = $this->get(route('finance.bills.export', ['period' => '2026-07']));
        $response->assertOk();
    }

    /** @test */
    public function student_address_is_encrypted_in_database(): void
    {
        $student = Student::create([
            'tenant_id' => $this->tenant1->id,
            'nis' => '009',
            'nisn' => '009009',
            'name' => 'Siswa Enkripsi',
            'birth_date' => '2015-05-15',
            'birth_place' => 'Surabaya',
            'gender' => 'L',
            'address' => 'Jalan Kebahagiaan No. 123',
            'status' => 'active',
        ]);

        // Pastikan di model didekripsi secara otomatis
        $this->assertEquals('Jalan Kebahagiaan No. 123', $student->address);
        $this->assertEquals('009009', $student->nisn);
        $this->assertEquals('2015-05-15', $student->birth_date->format('Y-m-d'));
        $this->assertEquals('Surabaya', $student->birth_place);
        $this->assertEquals('L', $student->gender);

        // Pastikan di database tersimpan dalam bentuk enkripsi (tidak terlihat plain text)
        $rawStudent = \Illuminate\Support\Facades\DB::table('students')->where('id', $student->id)->first();
        $this->assertNotEquals('Jalan Kebahagiaan No. 123', $rawStudent->address);
        $this->assertNotEquals('009009', $rawStudent->nisn);
        $this->assertNotEquals('2015-05-15', $rawStudent->birth_date);
        $this->assertNotEquals('Surabaya', $rawStudent->birth_place);
        $this->assertNotEquals('L', $rawStudent->gender);

        // Pastikan blind index terisi dengan benar
        $this->assertEquals(hash_hmac('sha256', '009009', config('app.key')), $rawStudent->nisn_bindex);
    }

    /** @test */
    public function creating_payment_records_audit_log(): void
    {
        $bill = Bill::create([
            'tenant_id' => $this->tenant1->id,
            'student_id' => $this->student->id,
            'period' => '2026-07',
            'component' => 'SPP',
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        $this->actingAs($this->bendahara);

        $response = $this->post(route('finance.payment.store', $bill), [
            'amount' => 500000,
            'payment_date' => '2026-07-15',
            'method' => 'cash',
        ]);

        // Cek database audit_logs memiliki record untuk Payment created
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->bendahara->id,
            'event' => 'created',
            'auditable_type' => Payment::class,
        ]);
    }
}
