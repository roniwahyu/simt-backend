<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AkademikModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $admin;
    protected User $guru;
    protected Student $student;
    protected SchoolYear $schoolYear;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        // Setup 2 tenants
        $this->tenant1 = Tenant::create([
            'name' => 'MTs Akademik Satu',
            'domain' => 'akad-satu',
            'status' => 'active',
            'activated_at' => now(),
        ]);
        $this->tenant2 = Tenant::create([
            'name' => 'MTs Akademik Dua',
            'domain' => 'akad-dua',
            'status' => 'active',
            'activated_at' => now(),
        ]);

        // Tenant 1 has Akademik module
        foreach (['Core', 'Student', 'Attendance', 'Akademik'] as $mod) {
            \App\Models\TenantModule::create([
                'tenant_id' => $this->tenant1->id,
                'module_code' => $mod,
                'active' => true,
            ]);
        }

        // Tenant 2 does not have Akademik module (for gating test)
        foreach (['Core', 'Student', 'Attendance'] as $mod) {
            \App\Models\TenantModule::create([
                'tenant_id' => $this->tenant2->id,
                'module_code' => $mod,
                'active' => true,
            ]);
        }

        // Provision roles
        $roleService = new TenantRoleService();
        $roleService->provisionForTenant($this->tenant1->id);
        $roleService->provisionForTenant($this->tenant2->id);

        // Setup users
        $this->admin = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Admin Akademik',
            'phone' => '628311111001',
            'email' => 'admin@akad-satu.sch.id',
            'password' => bcrypt('password'),
            'role_display' => 'admin_sekolah',
            'is_active' => true,
        ]);
        $roleService->assignRole($this->admin, 'admin_sekolah', $this->tenant1->id);

        $this->guru = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Guru Akademik',
            'phone' => '628311111002',
            'email' => 'guru@akad-satu.sch.id',
            'password' => bcrypt('password'),
            'role_display' => 'guru',
            'is_active' => true,
        ]);
        $roleService->assignRole($this->guru, 'guru', $this->tenant1->id);

        // Setup SchoolYear
        $this->schoolYear = SchoolYear::create([
            'tenant_id' => $this->tenant1->id,
            'name' => '2026/2027',
            'is_active' => true,
        ]);

        // Setup Student
        $this->student = Student::create([
            'tenant_id' => $this->tenant1->id,
            'nis' => 'AK-001',
            'nisn' => '00998811',
            'name' => 'Siswa Akademik',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function admin_can_access_akademik_dashboard(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('akademik.index'));
        $response->assertOk();
        $response->assertSee('Kurikulum');
    }

    /** @test */
    public function admin_can_add_school_class(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('akademik.classes.store'), [
            'name' => '7A',
            'grade' => '7',
            'school_year_id' => $this->schoolYear->id,
            'teacher_id' => $this->guru->id,
        ]);

        $response->assertRedirect(route('akademik.classes'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('school_classes', [
            'tenant_id' => $this->tenant1->id,
            'name' => '7A',
            'grade' => '7',
            'school_year_id' => $this->schoolYear->id,
            'teacher_id' => $this->guru->id,
        ]);
    }

    /** @test */
    public function admin_can_add_subject(): void
    {
        $class = SchoolClass::create([
            'tenant_id' => $this->tenant1->id,
            'school_year_id' => $this->schoolYear->id,
            'name' => '7A',
            'grade' => '7',
            'teacher_id' => $this->guru->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('akademik.subjects.store'), [
            'name' => 'Fiqih',
            'code' => 'FIQ-7',
            'school_class_id' => $class->id,
            'teacher_id' => $this->guru->id,
            'hours_per_week' => 2,
            'category' => 'AGAMA_ISLAM',
        ]);

        $response->assertRedirect(route('akademik.subjects'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subjects', [
            'tenant_id' => $this->tenant1->id,
            'name' => 'Fiqih',
            'code' => 'FIQ-7',
            'school_class_id' => $class->id,
            'teacher_id' => $this->guru->id,
            'hours_per_week' => 2,
            'category' => 'AGAMA_ISLAM',
        ]);
    }

    /** @test */
    public function guru_can_save_mass_grades(): void
    {
        $class = SchoolClass::create([
            'tenant_id' => $this->tenant1->id,
            'school_year_id' => $this->schoolYear->id,
            'name' => '7A',
            'grade' => '7',
            'teacher_id' => $this->guru->id,
        ]);
        $this->student->classes()->attach($class->id, ['school_year_id' => $this->schoolYear->id]);

        $subject = Subject::create([
            'tenant_id' => $this->tenant1->id,
            'school_class_id' => $class->id,
            'name' => 'Fiqih',
            'hours_per_week' => 2,
            'teacher_id' => $this->guru->id,
            'category' => 'AGAMA_ISLAM',
        ]);

        $this->actingAs($this->guru);

        $response = $this->post(route('grades.store'), [
            'subject_id' => $subject->id,
            'type' => 'UH1',
            'grades' => [
                [
                    'student_id' => $this->student->id,
                    'score' => 95,
                    'description' => 'Sangat paham materi thaharah',
                ]
            ]
        ]);

        $response->assertRedirect(route('grades.index', ['subject_id' => $subject->id]));
        
        $this->assertDatabaseHas('grades', [
            'tenant_id' => $this->tenant1->id,
            'student_id' => $this->student->id,
            'subject_id' => $subject->id,
            'type' => 'UH1',
            'score' => 95,
            'description' => 'Sangat paham materi thaharah',
        ]);
    }

    /** @test */
    public function can_view_rapor_and_export_pdf(): void
    {
        $class = SchoolClass::create([
            'tenant_id' => $this->tenant1->id,
            'school_year_id' => $this->schoolYear->id,
            'name' => '7A',
            'grade' => '7',
            'teacher_id' => $this->guru->id,
        ]);
        $this->student->classes()->attach($class->id, ['school_year_id' => $this->schoolYear->id]);

        $subject = Subject::create([
            'tenant_id' => $this->tenant1->id,
            'school_class_id' => $class->id,
            'name' => 'Fiqih',
            'hours_per_week' => 2,
            'teacher_id' => $this->guru->id,
            'category' => 'AGAMA_ISLAM',
        ]);

        Grade::create([
            'tenant_id' => $this->tenant1->id,
            'student_id' => $this->student->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->guru->id,
            'type' => 'UH1',
            'score' => 88.50,
            'description' => 'Baik',
        ]);

        $this->actingAs($this->guru);

        $response = $this->get(route('grades.rapor', ['student_id' => $this->student->id]));
        $response->assertOk();
        $response->assertSee('Siswa Akademik');

        // Test PDF Export
        $response = $this->get(route('grades.rapor', ['student_id' => $this->student->id, 'export' => 'pdf']));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function akademik_module_disabled_returns_403(): void
    {
        // Tenant 2 does not have Akademik module
        $adminT2 = User::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Admin T2',
            'phone' => '628322222001',
            'email' => 'admin@akad-dua.sch.id',
            'password' => bcrypt('password'),
            'role_display' => 'admin_sekolah',
            'is_active' => true,
        ]);
        $roleService = new TenantRoleService();
        $roleService->assignRole($adminT2, 'admin_sekolah', $this->tenant2->id);

        $this->actingAs($adminT2);

        $response = $this->get(route('akademik.index'));
        $response->assertStatus(403);
    }

    /** @test */
    public function wali_can_access_student_grades_and_rapor_via_api(): void
    {
        // Setup Wali user
        $roleService = new TenantRoleService();
        $wali = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Wali Akademik',
            'phone' => '628311111003',
            'email' => 'wali@akad-satu.sch.id',
            'password' => bcrypt('password'),
            'role_display' => 'wali',
            'is_active' => true,
        ]);
        $roleService->assignRole($wali, 'wali', $this->tenant1->id);
        $wali->guardianStudents()->attach($this->student->id, ['relation' => 'Ayah']);

        // Create classroom, subject, and grades
        $class = SchoolClass::create([
            'tenant_id' => $this->tenant1->id,
            'school_year_id' => $this->schoolYear->id,
            'name' => '7A',
            'grade' => '7',
            'teacher_id' => $this->guru->id,
        ]);
        $this->student->classes()->attach($class->id, ['school_year_id' => $this->schoolYear->id]);

        $subject = Subject::create([
            'tenant_id' => $this->tenant1->id,
            'school_class_id' => $class->id,
            'name' => 'Fiqih',
            'hours_per_week' => 2,
            'teacher_id' => $this->guru->id,
            'category' => 'AGAMA_ISLAM',
        ]);

        Grade::create([
            'tenant_id' => $this->tenant1->id,
            'student_id' => $this->student->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->guru->id,
            'type' => 'UH1',
            'score' => 90.00,
            'description' => 'Sangat Baik',
        ]);

        $token = $wali->createToken('test-token')->plainTextToken;
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'X-Tenant-Domain' => 'akad-satu',
            'Accept' => 'application/json',
        ];

        // 1. Get grades API
        $response = $this->getJson('/api/v1/students/' . $this->student->id . '/grades', $headers);
        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonFragment(['score' => 90]);

        // 2. Get rapor API
        $response = $this->getJson('/api/v1/students/' . $this->student->id . '/rapor', $headers);
        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonFragment(['predicate_pengetahuan' => 'D']); // score = 90 * 0.25 = 22.5 (D)
    }

    /** @test */
    public function wali_cannot_access_other_students_grades_via_api(): void
    {
        // Setup Wali user
        $roleService = new TenantRoleService();
        $wali = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Wali Akademik',
            'phone' => '628311111003',
            'email' => 'wali@akad-satu.sch.id',
            'password' => bcrypt('password'),
            'role_display' => 'wali',
            'is_active' => true,
        ]);
        $roleService->assignRole($wali, 'wali', $this->tenant1->id);

        // Setup other student not owned by this wali
        $studentLain = Student::create([
            'tenant_id' => $this->tenant1->id,
            'nis' => 'AK-002',
            'nisn' => '00998812',
            'name' => 'Siswa Lain',
            'status' => 'active',
        ]);

        $token = $wali->createToken('test-token')->plainTextToken;
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'X-Tenant-Domain' => 'akad-satu',
            'Accept' => 'application/json',
        ];

        // Wali tries to access other student's grades — SHOULD FAIL (403)
        $response = $this->getJson('/api/v1/students/' . $studentLain->id . '/grades', $headers);
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'code' => 'FORBIDDEN_OWNERSHIP',
        ]);
    }

    /** @test */
    public function akademik_api_module_disabled_returns_403(): void
    {
        // Setup Wali user on Tenant 2 (which does not have Akademik module)
        $roleService = new TenantRoleService();
        $waliT2 = User::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Wali Akademik T2',
            'phone' => '628322222003',
            'email' => 'wali@akad-dua.sch.id',
            'password' => bcrypt('password'),
            'role_display' => 'wali',
            'is_active' => true,
        ]);
        $roleService->assignRole($waliT2, 'wali', $this->tenant2->id);

        $studentT2 = Student::create([
            'tenant_id' => $this->tenant2->id,
            'nis' => 'AK-002',
            'nisn' => '00998812',
            'name' => 'Siswa T2',
            'status' => 'active',
        ]);
        $waliT2->guardianStudents()->attach($studentT2->id, ['relation' => 'Ayah']);

        $token = $waliT2->createToken('test-token')->plainTextToken;
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'X-Tenant-Domain' => 'akad-dua',
            'Accept' => 'application/json',
        ];

        $response = $this->getJson('/api/v1/students/' . $studentT2->id . '/grades', $headers);
        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'code' => 'MODULE_INACTIVE',
        ]);
    }
}
