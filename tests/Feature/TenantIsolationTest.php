<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * TenantIsolationTest — Verifikasi multi-tenant isolation
 *
 * 🔒 Membuktikan bahwa:
 * - Data tenant A TIDAK terlihat dari tenant B
 * - Global scope BelongsToTenant bekerja dengan benar
 * - Tenancy singleton memfilter query otomatis
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $adminT1;
    protected User $guruT2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenants
        $this->tenant1 = Tenant::create([
            'name' => 'MTs Al-Hikmah',
            'domain' => 'mts-alhikmah',
            'status' => 'active',
        ]);
        $this->tenant2 = Tenant::create([
            'name' => 'MTs An-Nur',
            'domain' => 'mts-annur',
            'status' => 'active',
        ]);

        // Create users
        $this->adminT1 = User::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Admin T1',
            'email' => 'admin@t1.test',
            'phone' => '628000000001',
            'password' => bcrypt('password'),
        ]);

        $this->guruT2 = User::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Guru T2',
            'email' => 'guru@t2.test',
            'phone' => '628000000002',
            'password' => bcrypt('password'),
        ]);

        // Create school year for both
        $sy1 = SchoolYear::create([
            'tenant_id' => $this->tenant1->id,
            'name' => '2026/2027',
            'is_active' => true,
        ]);
        $sy2 = SchoolYear::create([
            'tenant_id' => $this->tenant2->id,
            'name' => '2026/2027',
            'is_active' => true,
        ]);

        // Create classes for both
        SchoolClass::create([
            'tenant_id' => $this->tenant1->id,
            'school_year_id' => $sy1->id,
            'name' => '7A',
            'grade' => '7',
        ]);
        SchoolClass::create([
            'tenant_id' => $this->tenant2->id,
            'school_year_id' => $sy2->id,
            'name' => '7A',
            'grade' => '7',
        ]);

        // Create students for T1
        for ($i = 1; $i <= 5; $i++) {
            Student::create([
                'tenant_id' => $this->tenant1->id,
                'nis' => 'T1-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'name' => "Siswa T1-$i",
                'gender' => 'L',
                'status' => 'active',
            ]);
        }

        // Create students for T2
        for ($i = 1; $i <= 3; $i++) {
            Student::create([
                'tenant_id' => $this->tenant2->id,
                'nis' => 'T2-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'name' => "Siswa T2-$i",
                'gender' => 'P',
                'status' => 'active',
            ]);
        }

        // Clear tenant context
        app(Tenancy::class)->forgetTenant();
    }

    #[Test]
    public function student_query_is_filtered_by_tenant(): void
    {
        // Set context to T1
        app(Tenancy::class)->setTenant($this->tenant1);

        $students = Student::all();
        $this->assertCount(5, $students);
        $students->each(fn($s) => $this->assertEquals($this->tenant1->id, $s->tenant_id));
    }

    #[Test]
    public function tenant2_cannot_see_tenant1_students(): void
    {
        // Set context to T2
        app(Tenancy::class)->setTenant($this->tenant2);

        $students = Student::all();
        $this->assertCount(3, $students);
        $students->each(fn($s) => $this->assertEquals($this->tenant2->id, $s->tenant_id));
    }

    #[Test]
    public function without_tenant_global_scope_returns_all(): void
    {
        // Without tenant context, no filter applied (null tenant_id)
        $allStudents = Student::withoutTenant()->get();
        $this->assertCount(8, $allStudents); // 5 + 3
    }

    #[Test]
    public function for_tenant_scope_filters_correctly(): void
    {
        $t1Students = Student::forTenant($this->tenant1->id)->get();
        $this->assertCount(5, $t1Students);

        $t2Students = Student::forTenant($this->tenant2->id)->get();
        $this->assertCount(3, $t2Students);
    }

    #[Test]
    public function creating_student_auto_fills_tenant_id(): void
    {
        app(Tenancy::class)->setTenant($this->tenant1);

        $student = Student::create([
            'nis' => 'NEW-001',
            'name' => 'New Student',
            'gender' => 'L',
            'status' => 'active',
        ]);

        $this->assertEquals($this->tenant1->id, $student->tenant_id);
    }

    #[Test]
    public function tenant1_admin_cannot_access_tenant2_student_detail(): void
    {
        app(Tenancy::class)->setTenant($this->tenant1);

        // Try to find T2 student — should return null due to global scope
        $t2Student = Student::where('nis', 'T2-0001')->first();
        $this->assertNull($t2Student);
    }

    #[Test]
    public function tenant_isolation_works_for_classes(): void
    {
        app(Tenancy::class)->setTenant($this->tenant1);
        $this->assertCount(1, SchoolClass::all());

        app(Tenancy::class)->setTenant($this->tenant2);
        $this->assertCount(1, SchoolClass::all());
    }

    #[Test]
    public function switching_tenant_context_changes_data_visibility(): void
    {
        // Start with T1 — setUp menanam 5 siswa untuk T1
        app(Tenancy::class)->setTenant($this->tenant1);
        $this->assertCount(5, Student::all());

        // Switch to T2 — setUp menanam 3 siswa untuk T2
        app(Tenancy::class)->setTenant($this->tenant2);
        $this->assertCount(3, Student::all());

        // Switch back to T1 — masih 5 (isolasi konteks bekerja dua arah)
        app(Tenancy::class)->setTenant($this->tenant1);
        $this->assertCount(5, Student::all());

        // Buat 1 siswa BARU dalam konteks T1 → tenant_id otomatis terisi,
        // hanya terlihat di T1, tidak bocor ke T2 (test mandiri, tidak
        // bergantung pada urutan eksekusi test lain).
        Student::create([
            'nis' => 'T1-NEW',
            'name' => 'Siswa Baru T1',
            'gender' => 'L',
            'status' => 'active',
        ]);
        $this->assertCount(6, Student::all());

        app(Tenancy::class)->setTenant($this->tenant2);
        $this->assertCount(3, Student::all()); // T2 tetap 3, tidak terpengaruh
    }
}
