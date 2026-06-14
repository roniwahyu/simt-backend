<?php

namespace Tests\Feature;

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
 * StudentModuleTest — Test modul siswa (CRUD + Import)
 */
class StudentModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected SchoolYear $schoolYear;
    protected SchoolClass $class7A;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'MTs Test',
            'domain' => 'mts-test',
            'status' => 'active',
        ]);

        // Activate Student module
        TenantModule::create([
            'tenant_id' => $this->tenant->id,
            'module_code' => 'Student',
            'active' => true,
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Test',
            'email' => 'admin@test.mts',
            'phone' => '628000000099',
            'password' => bcrypt('password'),
        ]);

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
        ]);

        app(Tenancy::class)->setTenant($this->tenant);
    }

    #[Test]
    public function can_create_student(): void
    {
        $student = Student::create([
            'nis' => '0001',
            'nisn' => '0012345678',
            'name' => 'Ahmad Test',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('students', ['nis' => '0001', 'name' => 'Ahmad Test']);
        $this->assertEquals($this->tenant->id, $student->tenant_id);
    }

    #[Test]
    public function can_assign_student_to_class(): void
    {
        $student = Student::create([
            'nis' => '0002',
            'name' => 'Siti Test',
            'gender' => 'P',
            'status' => 'active',
        ]);

        $student->classes()->attach($this->class7A->id, ['school_year_id' => $this->schoolYear->id]);

        $this->assertCount(1, $student->classes);
        $this->assertEquals('7A', $student->classes->first()->name);
    }

    #[Test]
    public function can_update_student(): void
    {
        $student = Student::create([
            'nis' => '0003',
            'name' => 'Old Name',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $student->update(['name' => 'New Name']);

        $this->assertEquals('New Name', $student->fresh()->name);
    }

    #[Test]
    public function can_delete_student(): void
    {
        $student = Student::create([
            'nis' => '0004',
            'name' => 'To Delete',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $student->delete();

        $this->assertDatabaseMissing('students', ['nis' => '0004']);
    }

    #[Test]
    public function student_nis_is_unique_per_tenant(): void
    {
        Student::create(['nis' => '0005', 'name' => 'First', 'gender' => 'L', 'status' => 'active']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Student::create(['nis' => '0005', 'name' => 'Duplicate', 'gender' => 'P', 'status' => 'active']);
    }

    #[Test]
    public function same_nis_different_tenant_is_allowed(): void
    {
        Student::create(['nis' => '0006', 'name' => 'T1 Student', 'gender' => 'L', 'status' => 'active']);

        // Create another tenant
        $tenant2 = Tenant::create(['name' => 'MTs Test 2', 'domain' => 'mts-test2', 'status' => 'active']);
        app(Tenancy::class)->setTenant($tenant2);

        // Same NIS should work in different tenant
        $student2 = Student::create(['nis' => '0006', 'name' => 'T2 Student', 'gender' => 'P', 'status' => 'active']);
        $this->assertEquals($tenant2->id, $student2->tenant_id);
    }

    #[Test]
    public function student_guardian_relationship_works(): void
    {
        $student = Student::create(['nis' => '0007', 'name' => 'Student', 'gender' => 'L', 'status' => 'active']);

        $wali = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Wali Student',
            'email' => 'wali7@test.mts',
            'phone' => '628000000007',
            'password' => bcrypt('password'),
        ]);

        $student->guardians()->attach($wali->id, ['relation' => 'ayah']);

        $this->assertCount(1, $student->guardians);
        $this->assertEquals('ayah', $student->guardians->first()->pivot->relation);
    }

    #[Test]
    public function student_search_by_name(): void
    {
        Student::create(['nis' => 'S1', 'name' => 'Ahmad Rizki', 'gender' => 'L', 'status' => 'active']);
        Student::create(['nis' => 'S2', 'name' => 'Siti Rahma', 'gender' => 'P', 'status' => 'active']);
        Student::create(['nis' => 'S3', 'name' => 'Ahmad Fauzi', 'gender' => 'L', 'status' => 'active']);

        $results = Student::where('name', 'like', '%Ahmad%')->get();
        $this->assertCount(2, $results);
    }
}
