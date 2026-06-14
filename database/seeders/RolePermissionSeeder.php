<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Core
            'view_dashboard',
            'manage_users',
            'manage_roles',
            'manage_tenants',

            // Student
            'view_students',
            'create_students',
            'edit_students',
            'delete_students',
            'import_students',

            // Attendance
            'view_attendance',
            'mark_attendance',
            'edit_attendance',
            'view_attendance_rekap',

            // Finance
            'view_bills',
            'create_bills',
            'record_payment',
            'print_receipt',
            'send_reminders',

            // WhatsApp
            'wa.connect',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $roles = [
            'superadmin' => Permission::all()->pluck('name')->toArray(),
            'kepala_madrasah' => ['view_dashboard', 'view_students', 'view_attendance', 'view_attendance_rekap', 'view_bills'],
            'tu' => ['view_dashboard', 'view_students', 'create_students', 'edit_students', 'import_students', 'view_attendance', 'view_attendance_rekap'],
            'bendahara' => ['view_dashboard', 'view_bills', 'create_bills', 'record_payment', 'print_receipt', 'send_reminders'],
            'guru' => ['view_dashboard', 'view_students', 'view_attendance', 'mark_attendance', 'edit_attendance'],
            'wali' => ['view_dashboard'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }
    }
}
