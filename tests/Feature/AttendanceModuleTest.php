<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * AttendanceModuleTest — Test modul Presensi (Sprint 3)
 *
 * Membuktikan:
 * - Grid presensi tersimpan (bulk save) via route web
 * - Audit marked_by terisi guru yang input
 * - unique(student, date) — updateOrCreate, bukan duplikat
 * - Rekap bulanan dapat diakses
 * - Isolasi tenant pada data presensi
 * - Plug & play: modul nonaktif → 403
 */
class AttendanceModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $guru;
    protected SchoolYear $schoolYear;
    protected SchoolClass $class7A;
    protected Student $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->tenant = Tenant::create([
            'name' => 'MTs Presensi',
            'domain' => 'mts-presensi',
            'status' => 'active',
        ]);

        TenantModule::create([
            'tenant_id' => $this->tenant->id,
            'module_code' => 'Attendance',
            'active' => true,
        ]);

        $this->guru = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Presensi',
            'email' => 'guru@presensi.mts',
            'phone' => '628000000077',
            'password' => bcrypt('password'),
            'role_display' => 'guru',
        ]);

        app(Tenancy::class)->setTenant($this->tenant);
        
        $roleService = new \App\Services\TenantRoleService();
        $roleService->provisionForTenant($this->tenant->id);
        $roleService->assignRole($this->guru, 'guru', $this->tenant->id);

        $this->schoolYear = SchoolYear::create([
            'tenant_id' => $this->tenant->id,
            'name' => '2026/2027',
            'is_active' => true,
        ]);

        $this->class7A = SchoolClass::create([
            'tenant_id' => $this->tenant->id,
            'school_year_id' => $this->schoolYear->id,
            'name' => '7A',
            'grade' => '7',
            'teacher_id' => $this->guru->id,
        ]);

        $this->siswa = Student::create([
            'tenant_id' => $this->tenant->id,
            'nis' => 'P-001',
            'name' => 'Siswa Presensi',
            'gender' => 'L',
            'status' => 'active',
        ]);
        $this->siswa->classes()->attach($this->class7A->id, ['school_year_id' => $this->schoolYear->id]);
    }

    #[Test]
    public function guru_can_save_attendance_grid_and_marked_by_is_recorded(): void
    {
        $response = $this->actingAs($this->guru)->postJson(route('attendance.store'), [
            'class_id' => $this->class7A->id,
            'date' => now()->toDateString(),
            'records' => [
                ['student_id' => $this->siswa->id, 'status' => 'A'],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        app(Tenancy::class)->setTenant($this->tenant);
        $attendance = Attendance::where('student_id', $this->siswa->id)->first();
        $this->assertNotNull($attendance);
        $this->assertEquals('A', $attendance->status);
        $this->assertEquals($this->guru->id, $attendance->marked_by); // audit
        $this->assertEquals($this->tenant->id, $attendance->tenant_id); // auto-fill
    }

    #[Test]
    public function attendance_is_unique_per_student_per_date(): void
    {
        $date = now()->toDateString();

        $this->actingAs($this->guru)->postJson(route('attendance.store'), [
            'class_id' => $this->class7A->id,
            'date' => $date,
            'records' => [['student_id' => $this->siswa->id, 'status' => 'H']],
        ])->assertOk();

        // Submit lagi tanggal sama → updateOrCreate, bukan baris baru
        $this->actingAs($this->guru)->postJson(route('attendance.store'), [
            'class_id' => $this->class7A->id,
            'date' => $date,
            'records' => [['student_id' => $this->siswa->id, 'status' => 'S']],
        ])->assertOk();

        app(Tenancy::class)->setTenant($this->tenant);
        $this->assertEquals(1, Attendance::where('student_id', $this->siswa->id)->where('date', $date)->count());
        $this->assertEquals('S', Attendance::where('student_id', $this->siswa->id)->first()->status);
    }

    #[Test]
    public function monthly_recap_page_is_accessible(): void
    {
        $this->actingAs($this->guru)->postJson(route('attendance.store'), [
            'class_id' => $this->class7A->id,
            'date' => now()->toDateString(),
            'records' => [['student_id' => $this->siswa->id, 'status' => 'H']],
        ])->assertOk();

        $response = $this->actingAs($this->guru)->get(route('attendance.rekap', [
            'class_id' => $this->class7A->id,
            'month' => now()->format('Y-m'),
        ]));

        $response->assertOk();
        $response->assertSee($this->siswa->name);
    }

    #[Test]
    public function attendance_module_disabled_returns_403(): void
    {
        // Tenant lain tanpa modul Attendance aktif
        $tenant2 = Tenant::create(['name' => 'MTs NoAtt', 'domain' => 'mts-noatt', 'status' => 'active']);
        $guru2 = User::create([
            'tenant_id' => $tenant2->id, 'name' => 'Guru 2', 'email' => 'g2@noatt.mts',
            'phone' => '628000000066', 'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($guru2)->postJson(route('attendance.store'), [
            'class_id' => 1, 'date' => now()->toDateString(), 'records' => [],
        ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function attendance_is_isolated_per_tenant(): void
    {
        // Buat presensi di tenant 1
        Attendance::create([
            'tenant_id' => $this->tenant->id,
            'student_id' => $this->siswa->id,
            'class_id' => $this->class7A->id,
            'date' => now()->toDateString(),
            'status' => 'H',
        ]);

        // Tenant 2 tidak boleh melihat presensi tenant 1
        $tenant2 = Tenant::create(['name' => 'MTs Lain', 'domain' => 'mts-lain', 'status' => 'active']);
        app(Tenancy::class)->setTenant($tenant2);
        $this->assertCount(0, Attendance::all());

        app(Tenancy::class)->setTenant($this->tenant);
        $this->assertCount(1, Attendance::all());
    }

    #[Test]
    public function monthly_recap_export_is_accessible(): void
    {
        $this->actingAs($this->guru)->postJson(route('attendance.store'), [
            'class_id' => $this->class7A->id,
            'date' => now()->toDateString(),
            'records' => [['student_id' => $this->siswa->id, 'status' => 'H']],
        ])->assertOk();

        $response = $this->actingAs($this->guru)->get(route('attendance.rekap.export', [
            'class_id' => $this->class7A->id,
            'month' => now()->format('Y-m'),
        ]));

        $response->assertOk();
        $this->assertTrue(
            str_contains($response->headers->get('content-disposition'), 'attachment; filename=rekap_presensi_7a_')
        );
    }

    #[Test]
    public function guru_can_access_own_class_attendance_and_recap(): void
    {
        // Guru can access their own class
        $response = $this->actingAs($this->guru)->get(route('attendance.grid', ['class' => $this->class7A->id]));
        $response->assertOk();

        // Guru can access their own class recap
        $response = $this->actingAs($this->guru)->get(route('attendance.rekap', [
            'class_id' => $this->class7A->id,
            'month' => now()->format('Y-m'),
        ]));
        $response->assertOk();
    }

    #[Test]
    public function guru_cannot_access_other_teachers_class_attendance_or_recap(): void
    {
        // Create another teacher and class
        $guruLain = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Lain',
            'email' => 'guru_lain@presensi.mts',
            'phone' => '628000000088',
            'password' => bcrypt('password'),
            'role_display' => 'guru',
        ]);
        
        $roleService = new \App\Services\TenantRoleService();
        $roleService->assignRole($guruLain, 'guru', $this->tenant->id);

        $classLain = SchoolClass::create([
            'tenant_id' => $this->tenant->id,
            'school_year_id' => $this->schoolYear->id,
            'name' => '7B',
            'grade' => '7',
            'teacher_id' => $guruLain->id,
        ]);

        // Guru 1 (this->guru) attempts to access class7B which belongs to Guru Lain
        $response = $this->actingAs($this->guru)->get(route('attendance.grid', ['class' => $classLain->id]));
        $response->assertStatus(403);

        // Guru 1 attempts to view recap of class7B
        $response = $this->actingAs($this->guru)->get(route('attendance.rekap', [
            'class_id' => $classLain->id,
            'month' => now()->format('Y-m'),
        ]));
        $response->assertStatus(403);

        // Guru 1 attempts to save attendance of class7B
        $response = $this->actingAs($this->guru)->postJson(route('attendance.store'), [
            'class_id' => $classLain->id,
            'date' => now()->toDateString(),
            'records' => [['student_id' => $this->siswa->id, 'status' => 'H']],
        ]);
        $response->assertStatus(403);
    }

    #[Test]
    public function unauthorized_roles_blocked_from_attendance_module(): void
    {
        // Create a user with 'bendahara' role who has no attendance permissions
        $bendahara = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Bendahara Presensi',
            'email' => 'bendahara@presensi.mts',
            'phone' => '628000000099',
            'password' => bcrypt('password'),
            'role_display' => 'bendahara',
        ]);

        $roleService = new \App\Services\TenantRoleService();
        $roleService->assignRole($bendahara, 'bendahara', $this->tenant->id);

        // Try to access attendance index page
        $response = $this->actingAs($bendahara)->get(route('attendance.index'));
        $response->assertStatus(403);

        // Try to access attendance grid page
        $response = $this->actingAs($bendahara)->get(route('attendance.grid', ['class' => $this->class7A->id]));
        $response->assertStatus(403);

        // Try to store attendance
        $response = $this->actingAs($bendahara)->postJson(route('attendance.store'), [
            'class_id' => $this->class7A->id,
            'date' => now()->toDateString(),
            'records' => [['student_id' => $this->siswa->id, 'status' => 'H']],
        ]);
        $response->assertStatus(403);
    }
}
