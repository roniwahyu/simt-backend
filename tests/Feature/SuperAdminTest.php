<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularUser;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $roleService = new TenantRoleService();

        // Setup a tenant
        $this->tenant = Tenant::create([
            'name' => 'MTs SuperAdmin Test',
            'domain' => 'super-test',
            'status' => 'active',
        ]);

        $roleService->provisionForTenant($this->tenant->id);

        // Setup users
        $this->superAdmin = User::create([
            'tenant_id' => null, // Superadmin doesn't belong to a specific tenant
            'name' => 'Super Administrator',
            'phone' => '628999999999',
            'email' => 'super@simt.id',
            'password' => bcrypt('password'),
            'role_display' => 'superadmin',
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('superadmin');

        $this->regularUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Kepala Madrasah Test',
            'phone' => '628999999001',
            'email' => 'kamad@super-test.sch.id',
            'password' => bcrypt('password'),
            'role_display' => 'kepala_madrasah',
            'is_active' => true,
        ]);
        $roleService->assignRole($this->regularUser, 'kepala_madrasah', $this->tenant->id);
    }

    /** @test */
    public function super_admin_can_access_super_dashboard(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('super.dashboard'));

        $response->assertOk();
        $response->assertSee('Super Admin Panel');
        $response->assertSee('MTs SuperAdmin Test');
    }

    /** @test */
    public function regular_user_cannot_access_super_dashboard(): void
    {
        $this->actingAs($this->regularUser);

        $response = $this->get(route('super.dashboard'));

        $response->assertStatus(403);
    }

    /** @test */
    public function super_admin_can_create_new_tenant(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->post(route('super.tenant.store'), [
            'name' => 'MTs Gontor Baru',
            'domain' => 'gontor-baru',
            'phone' => '081234567890',
            'address' => 'Gontor, Ponorogo',
        ]);

        $response->assertRedirect(route('super.dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tenants', [
            'name' => 'MTs Gontor Baru',
            'domain' => 'gontor-baru',
            'status' => 'contracted',
        ]);
    }

    /** @test */
    public function super_admin_can_toggle_tenant_modules(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->put(route('super.tenant.update', $this->tenant), [
            'name' => 'MTs SuperAdmin Test (Updated)',
            'status' => 'active',
            'modules' => [
                'Core' => 1,
                'Student' => 1,
                'Attendance' => 0, // Disable Attendance
                'Finance' => 1,
            ]
        ]);

        $response->assertRedirect(route('super.tenant.edit', $this->tenant));

        $this->assertDatabaseHas('tenant_modules', [
            'tenant_id' => $this->tenant->id,
            'module_code' => 'Attendance',
            'active' => false,
        ]);
    }

    /** @test */
    public function super_admin_can_access_failed_jobs_page(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('super.failed-jobs'));

        $response->assertOk();
        $response->assertSee('Failed Queue Jobs');
    }

    /** @test */
    public function regular_user_cannot_access_failed_jobs_page(): void
    {
        $this->actingAs($this->regularUser);

        $response = $this->get(route('super.failed-jobs'));

        $response->assertStatus(403);
    }
}
