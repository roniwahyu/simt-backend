<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * TenantRoleService — Provisioning 6 role per tenant
 *
 * Spatie Permission dengan teams=true → setPermissionsTeamId(tenant_id)
 * Setiap role diprovisioning HANYA untuk team (tenant) yang aktif.
 *
 * Matriks Role → Permission:
 * - admin_sekolah   = semua permission
 * - kepala_madrasah  = dashboard + view/recap presensi + view tagihan
 * - tu               = students.* + attendance view/recap + wa.connect
 * - bendahara        = bills/payments/arrears
 * - guru             = students.view + attendance.mark/view
 * - wali             = ownership-based (tanpa permission admin)
 */
class TenantRoleService
{
    /**
     * Matriks Role → Permission (untuk tenant context)
     */
    public const ROLE_MATRIX = [
        'admin_sekolah' => [
            'view_dashboard', 'manage_users', 'manage_roles', 'manage_tenants',
            'view_students', 'create_students', 'edit_students', 'delete_students', 'import_students',
            'view_attendance', 'mark_attendance', 'edit_attendance', 'view_attendance_rekap',
            'view_bills', 'create_bills', 'record_payment', 'print_receipt', 'send_reminders',
            'wa.connect',
        ],
        'kepala_madrasah' => [
            'view_dashboard', 'view_students', 'view_attendance', 'view_attendance_rekap', 'view_bills',
        ],
        'tu' => [
            'view_dashboard', 'view_students', 'create_students', 'edit_students', 'import_students',
            'view_attendance', 'view_attendance_rekap', 'wa.connect',
        ],
        'bendahara' => [
            'view_dashboard', 'view_bills', 'create_bills', 'record_payment', 'print_receipt', 'send_reminders',
        ],
        'guru' => [
            'view_dashboard', 'view_students', 'view_attendance', 'mark_attendance', 'edit_attendance',
        ],
        'wali' => [
            'view_dashboard',
        ],
    ];

    /**
     * Provision semua role dan permission untuk sebuah tenant
     *
     * @param int $tenantId — tenant_id (= Spatie team_id)
     */
    public function provisionForTenant(int $tenantId): void
    {
        // Set team context
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        foreach (self::ROLE_MATRIX as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'team_id' => $tenantId,
            ]);
            $role->syncPermissions($permissions);
        }
    }

    /**
     * Assign role ke user dalam konteks tenant tertentu
     */
    public function assignRole(User $user, string $roleName, int $tenantId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        // Pastikan role ada untuk tenant ini
        Role::firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
            'team_id' => $tenantId,
        ]);

        $user->assignRole($roleName);
    }

    /**
     * Cek apakah user punya role tertentu di tenant tertentu
     */
    public function hasRoleInTenant(User $user, string $roleName, int $tenantId): bool
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        // Re-fetch user untuk menghindari stale cached roles
        $freshUser = User::find($user->id);
        return $freshUser?->hasRole($roleName) ?? false;
    }
}
