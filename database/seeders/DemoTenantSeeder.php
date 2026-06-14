<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Bill;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        // Create super admin
        $superAdmin = User::firstOrCreate(
            ['phone' => '081234567890'],
            [
                'name' => 'Super Admin SIMT',
                'email' => 'superadmin@simt.id',
                'password' => Hash::make('simt2026'),
                'role_display' => 'superadmin',
            ]
        );
        // Super-admin doesn't need team context
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
        $superAdmin->assignRole('superadmin');

        // === TENANT 1: MTs Al-Hikmah (30 students, 4 modules) ===
        $t1 = Tenant::firstOrCreate(
            ['domain' => 'alhikmah'],
            [
                'name' => 'MTs Al-Hikmah',
                'phone' => '081234567891',
                'address' => 'Jl. Al-Hikmah No.1, Malang',
                'status' => 'active',
                'activated_at' => now(),
            ]
        );

        foreach (['Core', 'Student', 'Attendance', 'Finance'] as $mod) {
            TenantModule::firstOrCreate(
                ['tenant_id' => $t1->id, 'module_code' => $mod],
                ['active' => true]
            );
        }

        $t1_admin = User::firstOrCreate(
            ['email' => 'ahmad@alhikmah.simt.id'],
            [
                'tenant_id' => $t1->id,
                'name' => 'Ahmad Fauzi (Admin)',
                'phone' => '081234567893',
                'password' => Hash::make('simt2026'),
                'role_display' => 'kepala_madrasah',
            ]
        );
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($t1->id);
        $t1_admin->assignRole('kepala_madrasah');

        $t1_guru = User::firstOrCreate(
            ['email' => 'guru@alhikmah.simt.id'],
            [
                'tenant_id' => $t1->id,
                'name' => 'Guru Ahmad',
                'phone' => '081234567894',
                'password' => Hash::make('simt2026'),
                'role_display' => 'guru',
            ]
        );
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($t1->id);
        $t1_guru->assignRole('guru');

        $t1_wali = User::firstOrCreate(
            ['phone' => '628520000001'],
            [
                'tenant_id' => $t1->id,
                'name' => 'Wali Siswa',
                'email' => 'wali@alhikmah.simt.id',
                'password' => Hash::make('simt2026'),
                'role_display' => 'wali',
            ]
        );
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($t1->id);
        $t1_wali->assignRole('wali');

        $sy1 = SchoolYear::firstOrCreate(
            ['tenant_id' => $t1->id, 'name' => '2026/2027'],
            [
                'start_date' => '2026-07-01',
                'end_date' => '2027-06-30',
                'is_active' => true,
            ]
        );

        $c1a = SchoolClass::firstOrCreate(
            ['tenant_id' => $t1->id, 'school_year_id' => $sy1->id, 'name' => '7A'],
            ['grade' => '7', 'teacher_id' => $t1_guru->id]
        );
        $c1b = SchoolClass::firstOrCreate(
            ['tenant_id' => $t1->id, 'school_year_id' => $sy1->id, 'name' => '7B'],
            ['grade' => '7', 'teacher_id' => $t1_guru->id]
        );
        $c1c = SchoolClass::firstOrCreate(
            ['tenant_id' => $t1->id, 'school_year_id' => $sy1->id, 'name' => '8A'],
            ['grade' => '8', 'teacher_id' => $t1_guru->id]
        );
        $t1_classes = [$c1a, $c1b, $c1c];

        $firstNames = ['Ahmad','Muhammad','Fatimah','Aisyah','Umar','Ali','Khadijah','Zainab','Bilal','Siti','Rizki','Putri','Nur','Dewi','Hafiz','Ibrahim','Rahma','Salsa','Farhan','Lestari','Andi','Budi','Citra','Dina','Eko','Fajar','Gita','Hani','Indra','Joko'];
        $lastNames = ['Fauzi','Rahman','Hidayah','Kusuma','Putra','Wijaya','Nurhaliza','Santoso','Dewi','Maulana','Saputra','Anggraini','Kurniawan','Setiawan','Laksono','Pratama','Sari','Ningsih','Ramadhan','Wulandari','Susanto','Siregar','Purnama','Oktaviani','Hermawan','Yulianto','Puspita','Iskandar','Surya','Wibowo'];

        for ($i = 1; $i <= 30; $i++) {
            $student = Student::firstOrCreate(
                ['tenant_id' => $t1->id, 'nis' => str_pad($i, 4, '0', STR_PAD_LEFT)],
                [
                    'nisn' => '00' . rand(1000000000, 9999999999),
                    'name' => $firstNames[$i % 30] . ' ' . $lastNames[$i % 30],
                    'gender' => $i % 2 === 0 ? 'L' : 'P',
                    'birth_date' => now()->subYears(12 + rand(0, 2))->subDays(rand(0, 365)),
                    'birth_place' => 'Malang',
                    'address' => 'Jl. Siswa No. ' . $i,
                    'status' => 'active',
                ]
            );
            $student->classes()->syncWithoutDetaching([
                $t1_classes[$i % 3]->id => ['school_year_id' => $sy1->id]
            ]);
            if ($i <= 10) {
                $student->guardians()->syncWithoutDetaching([$t1_wali->id => ['relation' => 'ayah']]);
            }
            $statuses = ['H','H','H','H','H','H','I','S','T','A'];
            Attendance::firstOrCreate(
                ['student_id' => $student->id, 'date' => now()->toDateString()],
                [
                    'tenant_id' => $t1->id,
                    'class_id' => $student->classes()->first()->id ?? $c1a->id,
                    'status' => $statuses[array_rand($statuses)],
                    'arrival_time' => '06:' . str_pad(rand(30, 55), 2, '0', STR_PAD_LEFT),
                    'marked_by' => $t1_guru->id,
                ]
            );
            Bill::firstOrCreate(
                ['tenant_id' => $t1->id, 'student_id' => $student->id, 'period' => now()->format('Y-m')],
                [
                    'component' => 'SPP',
                    'amount' => 150000,
                    'paid_amount' => $i <= 20 ? 150000 : 0,
                    'status' => $i <= 20 ? 'paid' : 'unpaid',
                    'due_date' => now()->endOfMonth()->toDateString(),
                ]
            );
        }

        // === TENANT 2: MTs An-Nur (5 students, 2 modules: Core + Student only) ===
        $t2 = Tenant::firstOrCreate(
            ['domain' => 'annur'],
            [
                'name' => 'MTs An-Nur',
                'phone' => '081234567892',
                'address' => 'Jl. An-Nur No.2, Malang',
                'status' => 'active',
                'activated_at' => now(),
            ]
        );

        foreach (['Core', 'Student'] as $mod) {
            TenantModule::firstOrCreate(
                ['tenant_id' => $t2->id, 'module_code' => $mod],
                ['active' => true]
            );
        }

        $t2_guru = User::firstOrCreate(
            ['email' => 'ahmad@annur.simt.id'],
            [
                'tenant_id' => $t2->id,
                'name' => 'Ahmad (Guru)',
                'phone' => '081234567895',
                'password' => Hash::make('simt2026'),
                'role_display' => 'guru',
            ]
        );
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($t2->id);
        $t2_guru->assignRole('guru');

        $sy2 = SchoolYear::firstOrCreate(
            ['tenant_id' => $t2->id, 'name' => '2026/2027'],
            [
                'start_date' => '2026-07-01',
                'end_date' => '2027-06-30',
                'is_active' => true,
            ]
        );

        $c2a = SchoolClass::firstOrCreate(
            ['tenant_id' => $t2->id, 'school_year_id' => $sy2->id, 'name' => '7A'],
            ['grade' => '7', 'teacher_id' => $t2_guru->id]
        );

        for ($i = 1; $i <= 5; $i++) {
            $student = Student::firstOrCreate(
                ['tenant_id' => $t2->id, 'nis' => str_pad($i, 4, '0', STR_PAD_LEFT)],
                [
                    'nisn' => '00' . rand(1000000000, 9999999999),
                    'name' => 'Siswa T2-' . $i,
                    'gender' => 'L',
                    'birth_date' => now()->subYears(12)->subDays(rand(0, 365)),
                    'birth_place' => 'Malang',
                    'address' => 'Jl. T2 No.' . $i,
                    'status' => 'active',
                ]
            );
            $student->classes()->syncWithoutDetaching([
                $c2a->id => ['school_year_id' => $sy2->id]
            ]);
        }
    }
}
