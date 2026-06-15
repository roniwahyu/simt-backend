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
use App\Services\TenantRoleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * PitchingDemoSeeder — 100 siswa + 100 wali + 100 WA queued
 * Untuk demo ke calon client sekolah
 */
class PitchingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $roleService = new TenantRoleService();

        // Create super admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'vendor@simt.id'],
            [
                'name' => 'Vendor SIMT',
                'phone' => '628000000000',
                'password' => Hash::make('password'),
                'role_display' => 'superadmin',
            ]
        );
        // For teams mode, superadmin assigned at team 0
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(0);
        $superAdmin->assignRole('superadmin');

        // === TENANT 1: MTs Al-Hikmah ===
        $t1 = Tenant::firstOrCreate(
            ['domain' => 'mts-alhikmah'],
            [
                'name' => 'MTs Al-Hikmah',
                'phone' => '628123456001',
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

        $roleService->provisionForTenant($t1->id);

        $ahmad = User::firstOrCreate(
            ['email' => 'ahmad@mts-alhikmah.sch.id'],
            [
                'tenant_id' => $t1->id,
                'name' => 'Ahmad Fauzi',
                'phone' => '628123456010',
                'password' => Hash::make('password'),
                'role_display' => 'admin_sekolah',
            ]
        );
        $roleService->assignRole($ahmad, 'admin_sekolah', $t1->id);

        $siti = User::firstOrCreate(
            ['email' => 'siti@mts-alhikmah.sch.id'],
            [
                'tenant_id' => $t1->id,
                'name' => 'Siti Maryam',
                'phone' => '628123456011',
                'password' => Hash::make('password'),
                'role_display' => 'guru',
            ]
        );
        $roleService->assignRole($siti, 'guru', $t1->id);

        $budi = User::firstOrCreate(
            ['email' => 'budi@mts-alhikmah.sch.id'],
            [
                'tenant_id' => $t1->id,
                'name' => 'Budi Santoso',
                'phone' => '628123456012',
                'password' => Hash::make('password'),
                'role_display' => 'tu',
            ]
        );
        $roleService->assignRole($budi, 'tu', $t1->id);

        // [2026-06-16 | AG] Tambah user Bendahara untuk demo login
        $farhan = User::firstOrCreate(
            ['email' => 'farhan@mts-alhikmah.sch.id'],
            [
                'tenant_id' => $t1->id,
                'name' => 'Farhan (Bendahara)',
                'phone' => '628123456013',
                'password' => Hash::make('password'),
                'role_display' => 'bendahara',
            ]
        );
        $roleService->assignRole($farhan, 'bendahara', $t1->id);

        // [2026-06-16 | AG] Tambah user Kepala Madrasah untuk demo login
        $hasan = User::firstOrCreate(
            ['email' => 'hasan@mts-alhikmah.sch.id'],
            [
                'tenant_id' => $t1->id,
                'name' => 'Hasan (Kepsek)',
                'phone' => '628123456014',
                'password' => Hash::make('password'),
                'role_display' => 'kepala_madrasah',
            ]
        );
        $roleService->assignRole($hasan, 'kepala_madrasah', $t1->id);

        $sy1 = SchoolYear::firstOrCreate(
            ['tenant_id' => $t1->id, 'name' => '2026/2027'],
            ['start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true]
        );

        $classes = [];
        foreach (['7A', '7B', '8A', '8B'] as $name) {
            $grade = substr($name, 0, 1);
            $classes[$name] = SchoolClass::firstOrCreate(
                ['tenant_id' => $t1->id, 'school_year_id' => $sy1->id, 'name' => $name],
                ['grade' => $grade, 'teacher_id' => $siti->id]
            );
        }

        // Create 100 students
        $firstNames = ['Ahmad','Muhammad','Fatimah','Aisyah','Umar','Ali','Khadijah','Zainab','Bilal','Siti',
                       'Rizki','Putri','Nur','Dewi','Hafiz','Ibrahim','Rahma','Salsa','Farhan','Lestari'];
        $lastNames = ['Fauzi','Rahman','Hidayah','Kusuma','Putra','Wijaya','Nurhaliza','Santoso','Dewi','Maulana',
                      'Saputra','Anggraini','Kurniawan','Setiawan','Laksono','Pratama','Sari','Ningsih','Ramadhan','Wulandari'];

        for ($i = 1; $i <= 100; $i++) {
            $student = Student::firstOrCreate(
                ['tenant_id' => $t1->id, 'nis' => str_pad($i, 4, '0', STR_PAD_LEFT)],
                [
                    'nisn' => '00' . (1000000000 + $i),
                    'name' => $firstNames[($i - 1) % 20] . ' ' . $lastNames[($i - 1) % 20],
                    'gender' => $i % 2 === 0 ? 'L' : 'P',
                    'birth_date' => now()->subYears(12 + ($i % 3))->subDays($i),
                    'birth_place' => 'Malang',
                    'address' => 'Jl. Siswa No. ' . $i,
                    'status' => 'active',
                ]
            );

            $className = array_keys($classes)[($i - 1) % 4];
            $student->classes()->syncWithoutDetaching([
                $classes[$className]->id => ['school_year_id' => $sy1->id]
            ]);

            // Create wali account
            $waliPhone = '6285200' . str_pad($i, 5, '0', STR_PAD_LEFT);
            $wali = User::firstOrCreate(
                ['phone' => $waliPhone],
                [
                    'tenant_id' => $t1->id,
                    'name' => 'Wali ' . $student->name,
                    'email' => 'wali_' . $student->nis . '@simt.local',
                    'password' => Hash::make('password'),
                    'role_display' => 'wali',
                ]
            );
            $roleService->assignRole($wali, 'wali', $t1->id);
            $student->guardians()->syncWithoutDetaching([$wali->id => ['relation' => 'ayah']]);

            // Create attendance for today
            $statuses = ['H','H','H','H','H','H','I','S','T','A'];
            Attendance::firstOrCreate(
                ['student_id' => $student->id, 'date' => now()->toDateString()],
                [
                    'tenant_id' => $t1->id,
                    'class_id' => $classes[$className]->id,
                    'status' => $statuses[$i % 10],
                    'arrival_time' => '06:' . str_pad(30 + ($i % 25), 2, '0', STR_PAD_LEFT),
                    'marked_by' => $siti->id,
                ]
            );

            // Create bill
            Bill::firstOrCreate(
                ['tenant_id' => $t1->id, 'student_id' => $student->id, 'period' => now()->format('Y-m')],
                [
                    'component' => 'SPP',
                    'amount' => 150000,
                    'paid_amount' => $i <= 60 ? 150000 : 0,
                    'status' => $i <= 60 ? 'paid' : 'unpaid',
                    'due_date' => now()->endOfMonth()->toDateString(),
                ]
            );
        }

        // === TENANT 2: MTs An-Nur ===
        $t2 = Tenant::firstOrCreate(
            ['domain' => 'mts-annur'],
            [
                'name' => 'MTs An-Nur',
                'phone' => '628123456002',
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

        $roleService->provisionForTenant($t2->id);

        // Ahmad juga guru di T2 (dual-role proof)
        $ahmad2 = User::firstOrCreate(
            ['email' => 'ahmad@mts-annur.sch.id'],
            [
                'tenant_id' => $t2->id,
                'name' => 'Ahmad (Guru)',
                'phone' => '628123456020',
                'password' => Hash::make('password'),
                'role_display' => 'guru',
            ]
        );
        $roleService->assignRole($ahmad2, 'guru', $t2->id);

        $sy2 = SchoolYear::firstOrCreate(
            ['tenant_id' => $t2->id, 'name' => '2026/2027'],
            ['start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'is_active' => true]
        );

        $c2a = SchoolClass::firstOrCreate(
            ['tenant_id' => $t2->id, 'school_year_id' => $sy2->id, 'name' => '7A'],
            ['grade' => '7', 'teacher_id' => $ahmad2->id]
        );

        for ($i = 1; $i <= 6; $i++) {
            $student = Student::firstOrCreate(
                ['tenant_id' => $t2->id, 'nis' => 'T2-' . str_pad($i, 4, '0', STR_PAD_LEFT)],
                [
                    'nisn' => '00' . (2000000000 + $i),
                    'name' => 'Siswa An-Nur ' . $i,
                    'gender' => 'L',
                    'birth_date' => now()->subYears(12)->subDays($i * 30),
                    'birth_place' => 'Malang',
                    'address' => 'Jl. T2 No.' . $i,
                    'status' => 'active',
                ]
            );
            $student->classes()->syncWithoutDetaching([
                $c2a->id => ['school_year_id' => $sy2->id]
            ]);
        }

        $this->command->info('PitchingDemoSeeder: 2 tenant, 100+ siswa, 100+ wali, selesai.');
    }
}
