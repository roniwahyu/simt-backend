<?php

namespace Tests\Feature;

use App\Jobs\SendWaNotification;
use App\Models\Bill;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FinanceModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $adminT1;
    protected User $adminT2;
    protected SchoolYear $schoolYearT1;
    protected SchoolYear $schoolYearT2;
    protected Student $studentT1;
    protected User $guardianT1;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Tenants
        $this->tenant1 = Tenant::create([
            'name' => 'MTs Al-Hikmah',
            'domain' => 'mts-alhikmah',
            'status' => 'active',
        ]);
        $this->tenant2 = Tenant::create([
            'name' => 'MTs An-Nur',
            'domain' => 'mts-annur',
            'status' => 'active',
        ]);

        // Enable Finance module for both
        TenantModule::create([
            'tenant_id' => $this->tenant1->id,
            'module_code' => 'Finance',
            'active' => true,
        ]);
        TenantModule::create([
            'tenant_id' => $this->tenant2->id,
            'module_code' => 'Finance',
            'active' => true,
        ]);

        // Create Admin Users
        $this->adminT1 = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Admin T1',
            'email' => 'admin@t1.test',
            'phone' => '628000000001',
            'password' => bcrypt('password'),
        ]);
        $this->adminT2 = User::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Admin T2',
            'email' => 'admin@t2.test',
            'phone' => '628000000002',
            'password' => bcrypt('password'),
        ]);

        // Create Active School Year for both
        $this->schoolYearT1 = SchoolYear::create([
            'tenant_id' => $this->tenant1->id,
            'name' => '2026/2027',
            'is_active' => true,
        ]);
        $this->schoolYearT2 = SchoolYear::create([
            'tenant_id' => $this->tenant2->id,
            'name' => '2026/2027',
            'is_active' => true,
        ]);

        // Create active student for T1
        $this->studentT1 = Student::create([
            'tenant_id' => $this->tenant1->id,
            'nis' => '1111',
            'name' => 'Siswa T1',
            'gender' => 'L',
            'status' => 'active',
        ]);

        // Create a Guardian and connect to the student
        $this->guardianT1 = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Wali Siswa T1',
            'email' => 'wali@t1.test',
            'phone' => '6281234567890',
            'password' => bcrypt('password'),
        ]);
        $this->studentT1->guardians()->attach($this->guardianT1->id, ['relation' => 'Ayah']);

        // Set default tenant context to tenant1
        app(Tenancy::class)->setTenant($this->tenant1);
    }

    #[Test]
    public function bills_page_is_accessible(): void
    {
        $response = $this->actingAs($this->adminT1)->get(route('finance.bills'));

        $response->assertOk();
        $response->assertSee('Tagihan & Pembayaran SPP', false);
    }

    #[Test]
    public function bendahara_can_generate_bills_without_auto_notify(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->adminT1)->post(route('finance.bills.generate'), [
            'period' => '2026-06',
            'component' => 'SPP',
            'amount' => 250000,
            'due_date' => '2026-06-10',
            'auto_notify' => '0',
        ]);

        $response->assertRedirect(route('finance.bills'));
        $response->assertSessionHas('success', 'Tagihan 1 siswa berhasil dibuat.');

        // Verify bill is created in database
        $this->assertDatabaseHas('bills', [
            'tenant_id' => $this->tenant1->id,
            'student_id' => $this->studentT1->id,
            'period' => '2026-06',
            'amount' => 250000,
            'status' => 'unpaid',
        ]);

        // Verify NO WA jobs were dispatched
        Queue::assertNothingPushed();
    }

    #[Test]
    public function bendahara_can_generate_bills_with_auto_notify(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->adminT1)->post(route('finance.bills.generate'), [
            'period' => '2026-06',
            'component' => 'SPP',
            'amount' => 250000,
            'due_date' => '2026-06-10',
            'auto_notify' => '1',
        ]);

        $response->assertRedirect(route('finance.bills'));
        $response->assertSessionHas('success', 'Tagihan 1 siswa berhasil dibuat. Dan 1 notifikasi WA diantrikan.');

        // Verify bill is created
        $this->assertDatabaseHas('bills', [
            'student_id' => $this->studentT1->id,
            'period' => '2026-06',
            'amount' => 250000,
        ]);

        // Verify WA Job was dispatched with correct payload
        Queue::assertPushed(SendWaNotification::class, function ($job) {
            return $job->tenantId === $this->tenant1->id
                && $job->toPhone === '6281234567890'
                && $job->type === 'bill_reminder'
                && $job->payload['student_name'] === 'Siswa T1'
                && $job->payload['amount'] === 250000.0;
        });
    }

    #[Test]
    public function bendahara_can_send_manual_reminder(): void
    {
        Queue::fake();

        // Create a bill first
        $bill = Bill::create([
            'tenant_id' => $this->tenant1->id,
            'student_id' => $this->studentT1->id,
            'period' => '2026-06',
            'component' => 'SPP',
            'amount' => 250000,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->adminT1)->post(route('finance.reminders'), [
            'bill_ids' => [$bill->id],
        ]);

        $response->assertRedirect(route('finance.bills'));
        $response->assertSessionHas('success', '1 pengingat WA diantrikan.');

        // Verify WA Job was dispatched
        Queue::assertPushed(SendWaNotification::class, function ($job) use ($bill) {
            return $job->tenantId === $this->tenant1->id
                && $job->toPhone === '6281234567890'
                && $job->type === 'bill_reminder'
                && $job->payload['student_name'] === 'Siswa T1'
                && $job->payload['amount'] === 250000.0;
        });
    }

    #[Test]
    public function finance_module_disabled_returns_403(): void
    {
        // Deactivate Finance module
        TenantModule::where('tenant_id', $this->tenant1->id)
            ->where('module_code', 'Finance')
            ->update(['active' => false]);

        $response = $this->actingAs($this->adminT1)->get(route('finance.bills'));

        $response->assertStatus(403);
    }

    #[Test]
    public function bills_isolated_per_tenant(): void
    {
        // Switch context to tenant2 and generate a bill there
        app(Tenancy::class)->setTenant($this->tenant2);
        
        $studentT2 = Student::create([
            'tenant_id' => $this->tenant2->id,
            'nis' => '2222',
            'name' => 'Siswa T2',
            'gender' => 'P',
            'status' => 'active',
        ]);

        $billT2 = Bill::create([
            'tenant_id' => $this->tenant2->id,
            'student_id' => $studentT2->id,
            'period' => '2026-06',
            'component' => 'SPP',
            'amount' => 300000,
            'status' => 'unpaid',
        ]);

        // Access bills from tenant1 admin context
        app(Tenancy::class)->setTenant($this->tenant1);
        $response = $this->actingAs($this->adminT1)->get(route('finance.bills'));
        
        $response->assertOk();
        // Should NOT see Tenant 2's bill amount
        $response->assertDontSee('300.000');
    }
}
