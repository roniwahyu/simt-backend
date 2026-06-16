<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Student;
use App\Models\AuditLog;
use App\Services\TenantRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $tenantAdminT1;
    protected User $tenantTUT1;
    protected User $tenantAdminT2;
    protected Tenant $tenant1;
    protected Tenant $tenant2;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $roleService = new TenantRoleService();

        // Setup 2 tenants
        $this->tenant1 = Tenant::create([
            'name' => 'MTs Audit Satu',
            'domain' => 'audit-satu',
            'status' => 'active',
        ]);
        $this->tenant2 = Tenant::create([
            'name' => 'MTs Audit Dua',
            'domain' => 'audit-dua',
            'status' => 'active',
        ]);

        $roleService->provisionForTenant($this->tenant1->id);
        $roleService->provisionForTenant($this->tenant2->id);

        // Provision modules
        foreach (['Core', 'Student'] as $mod) {
            \App\Models\TenantModule::create([
                'tenant_id' => $this->tenant1->id,
                'module_code' => $mod,
                'active' => true,
            ]);
            \App\Models\TenantModule::create([
                'tenant_id' => $this->tenant2->id,
                'module_code' => $mod,
                'active' => true,
            ]);
        }

        // Setup users
        $this->superAdmin = User::create([
            'tenant_id' => null,
            'name' => 'Super Administrator',
            'phone' => '628999999999',
            'email' => 'super@simt.id',
            'password' => bcrypt('password'),
            'role_display' => 'superadmin',
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('superadmin');

        $this->tenantAdminT1 = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Kamad T1',
            'phone' => '628999999001',
            'email' => 'kamad@audit-satu.sch.id',
            'password' => bcrypt('password'),
            'role_display' => 'kepala_madrasah',
            'is_active' => true,
        ]);
        $roleService->assignRole($this->tenantAdminT1, 'kepala_madrasah', $this->tenant1->id);

        $this->tenantTUT1 = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'TU T1',
            'phone' => '628999999003',
            'email' => 'tu@audit-satu.sch.id',
            'password' => bcrypt('password'),
            'role_display' => 'tu',
            'is_active' => true,
        ]);
        $roleService->assignRole($this->tenantTUT1, 'tu', $this->tenant1->id);

        $this->tenantAdminT2 = User::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Kamad T2',
            'phone' => '628999999002',
            'email' => 'kamad@audit-dua.sch.id',
            'password' => bcrypt('password'),
            'role_display' => 'kepala_madrasah',
            'is_active' => true,
        ]);
        $roleService->assignRole($this->tenantAdminT2, 'kepala_madrasah', $this->tenant2->id);
    }

    /** @test */
    public function creating_student_records_audit_log_with_correct_tenant_id(): void
    {
        $this->actingAs($this->tenantTUT1);

        $response = $this->post(route('students.store'), [
            'name' => 'Siswa Baru Audit',
            'nis' => 'NIS001',
            'gender' => 'L',
        ]);

        $response->assertRedirect(route('students.index'));

        // Cek database audit_logs
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->tenantTUT1->id,
            'event' => 'created',
            'auditable_type' => Student::class,
        ]);
    }

    /** @test */
    public function super_admin_can_view_all_audit_logs_with_tenant_filter(): void
    {
        // Buat dummy logs di kedua tenant
        AuditLog::create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->tenantAdminT1->id,
            'event' => 'created',
            'auditable_type' => Student::class,
            'auditable_id' => 1,
            'new_values' => ['name' => 'Siswa T1'],
        ]);

        AuditLog::create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->tenantAdminT2->id,
            'event' => 'created',
            'auditable_type' => Student::class,
            'auditable_id' => 2,
            'new_values' => ['name' => 'Siswa T2'],
        ]);

        $this->actingAs($this->superAdmin);

        // Lihat semua log
        $response = $this->get(route('super.audit-logs'));
        $response->assertOk();
        $response->assertSee('Siswa T1');
        $response->assertSee('Siswa T2');

        // Filter tenant 1
        $response = $this->get(route('super.audit-logs', ['tenant_id' => $this->tenant1->id]));
        $response->assertOk();
        $response->assertSee('Siswa T1');
        $response->assertDontSee('Siswa T2');
    }

    /** @test */
    public function tenant_admin_can_only_view_their_own_tenant_audit_logs(): void
    {
        // Buat dummy logs di kedua tenant
        AuditLog::create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->tenantAdminT1->id,
            'event' => 'created',
            'auditable_type' => Student::class,
            'auditable_id' => 1,
            'new_values' => ['name' => 'Siswa T1'],
        ]);

        AuditLog::create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->tenantAdminT2->id,
            'event' => 'created',
            'auditable_type' => Student::class,
            'auditable_id' => 2,
            'new_values' => ['name' => 'Siswa T2'],
        ]);

        // Login sebagai Admin T1
        $this->actingAs($this->tenantAdminT1);

        $response = $this->get(route('audit-logs'));
        $response->assertOk();
        $response->assertSee('Siswa T1');
        $response->assertDontSee('Siswa T2');
    }
}
