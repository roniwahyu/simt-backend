<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Nwidart\Modules\Facades\Module;
use Laravel\Sanctum\Sanctum;

class ModuleLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $parent;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->tenant = Tenant::create([
            'name' => 'MTs Dynamic Test',
            'domain' => 'mts-dynamic',
            'status' => 'active',
        ]);

        // Activate core / student modules in tenant
        TenantModule::create([
            'tenant_id' => $this->tenant->id,
            'module_code' => 'Core',
            'active' => true,
        ]);
        TenantModule::create([
            'tenant_id' => $this->tenant->id,
            'module_code' => 'Student',
            'active' => true,
        ]);

        $this->parent = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Wali Murid',
            'email' => 'wali_murid@test.mts',
            'phone' => '62812345678',
            'password' => bcrypt('password'),
        ]);

        $roleService = new \App\Services\TenantRoleService();
        $roleService->provisionForTenant($this->tenant->id);
        $roleService->assignRole($this->parent, 'wali', $this->tenant->id);

        $this->student = Student::create([
            'tenant_id' => $this->tenant->id,
            'nis' => '9999',
            'name' => 'Murid Tahfiz',
            'gender' => 'L',
            'status' => 'active',
            'student_password' => 'siswa123',
        ]);

        $this->student->guardians()->attach($this->parent->id, ['relation' => 'ayah']);
    }

    public function test_module_lifecycle_install_uninstall_and_gating(): void
    {
        // 1. Initially, Tahfiz is not active. Let's uninstall it first if it's there
        // Note: We'll run the install command to verify setup
        $this->artisan('simt:module install Tahfiz')
            ->assertExitCode(0);

        // Verification after install:
        // - Tahfiz module must be enabled globally
        $this->assertTrue(Module::isEnabled('Tahfiz'));
        
        // - The database table 'tahfiz_records' must exist in DB
        $this->assertTrue(Schema::hasTable('tahfiz_records'));

        // - The module is active in tenant_modules for our test tenant
        $this->assertTrue($this->tenant->hasModule('Tahfiz'));

        // 2. Test dynamic gating on Ortu Portal API
        // Under parent login
        Sanctum::actingAs($this->parent);

        // Get student dashboard
        $response = $this->withHeader('X-Tenant-Domain', $this->tenant->domain)
            ->getJson("/api/v1/portal/students/{$this->student->id}/student-dashboard");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'tahfiz' => ['totalRecords', 'latestRecords']
                ]
            ]);

        // 3. Test deactivating the module at tenant subscription level (Database-driven toggle)
        TenantModule::where('tenant_id', $this->tenant->id)
            ->where('module_code', 'Tahfiz')
            ->update(['active' => false]);

        // Dynamic change should be instant without code-level changes or flicker
        $response = $this->withHeader('X-Tenant-Domain', $this->tenant->domain)
            ->getJson("/api/v1/portal/students/{$this->student->id}/student-dashboard");

        $response->assertStatus(200);
        // It must return fallback empty values gracefully instead of crashing
        $this->assertEquals(0, $response->json('data.tahfiz.totalRecords'));
        $this->assertEmpty($response->json('data.tahfiz.latestRecords'));

        // 4. Test Uninstall
        $this->artisan('simt:module uninstall Tahfiz')
            ->assertExitCode(0);

        // Verification after uninstall:
        // - Tahfiz module must be disabled globally
        $this->assertFalse(Module::isEnabled('Tahfiz'));

        // - The database table 'tahfiz_records' should be dropped/reset
        $this->assertFalse(Schema::hasTable('tahfiz_records'));

        // - The subscription must be inactive in tenant_modules
        $this->assertFalse($this->tenant->fresh()->hasModule('Tahfiz'));
    }
}
