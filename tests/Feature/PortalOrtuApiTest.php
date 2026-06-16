<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class PortalOrtuApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\PitchingDemoSeeder::class);
    }

    public function test_student_can_login_with_correct_credentials()
    {
        $response = $this->postJson('/api/v1/auth/student-login', [
            'nis' => '0001',
            'password' => 'siswa123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'student' => ['id', 'name', 'nis', 'tenant'],
                    'token'
                ]
            ]);
    }

    public function test_student_login_fails_with_incorrect_password()
    {
        $response = $this->postJson('/api/v1/auth/student-login', [
            'nis' => '0001',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401);
    }

    public function test_parent_can_login_with_correct_credentials()
    {
        $response = $this->postJson('/api/v1/auth/parent-login', [
            'email' => 'wali_0001@simt.local',
            'password' => 'password'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'students',
                    'token'
                ]
            ]);
    }

    public function test_authenticated_parent_can_access_child_dashboard()
    {
        $student = Student::where('nis', '0001')->first();
        $parent = User::where('email', 'wali_0001@simt.local')->first();
        $tenant = $student->tenant;

        Sanctum::actingAs($parent);

        $response = $this->withHeader('X-Tenant-Domain', $tenant->domain)
            ->getJson("/api/v1/portal/students/{$student->id}/dashboard");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'student' => ['id', 'name', 'classroom', 'tenant'],
                    'attendanceSummary',
                    'grades',
                    'payments',
                    'announcements'
                ]
            ]);
    }

    public function test_parent_cannot_access_other_students_dashboard_idor()
    {
        $student1 = Student::where('nis', '0001')->first();
        $student2 = Student::where('nis', '0002')->first();
        $parent1 = User::where('email', 'wali_0001@simt.local')->first();
        $tenant = $student1->tenant;

        Sanctum::actingAs($parent1);

        $response = $this->withHeader('X-Tenant-Domain', $tenant->domain)
            ->getJson("/api/v1/portal/students/{$student2->id}/dashboard");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'FORBIDDEN_OWNERSHIP'
            ]);
    }

    public function test_authenticated_student_can_access_own_student_dashboard()
    {
        $student = Student::where('nis', '0001')->first();
        $tenant = $student->tenant;

        Sanctum::actingAs($student);

        $response = $this->withHeader('X-Tenant-Domain', $tenant->domain)
            ->getJson("/api/v1/portal/students/{$student->id}/student-dashboard");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'student',
                    'attendanceSummary',
                    'grades',
                    'payments',
                    'announcements',
                    'schedules',
                    'violations',
                    'achievements',
                    'tahfiz'
                ]
            ]);
    }

    public function test_student_cannot_access_other_students_student_dashboard()
    {
        $student1 = Student::where('nis', '0001')->first();
        $student2 = Student::where('nis', '0002')->first();
        $tenant = $student1->tenant;

        Sanctum::actingAs($student1);

        $response = $this->withHeader('X-Tenant-Domain', $tenant->domain)
            ->getJson("/api/v1/portal/students/{$student2->id}/student-dashboard");

        $response->assertStatus(403);
    }

    public function test_can_view_grade_details()
    {
        $student = Student::where('nis', '0001')->first();
        $subject = Subject::where('school_class_id', $student->currentClass()->id)->first();
        $tenant = $student->tenant;

        Sanctum::actingAs($student);

        $response = $this->withHeader('X-Tenant-Domain', $tenant->domain)
            ->getJson("/api/v1/portal/students/{$student->id}/subjects/{$subject->id}/grade-details");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'details' => ['tugas', 'harian', 'uts', 'uas', 'akhir'],
                    'averages' => ['tugas', 'harian', 'uts', 'uas', 'akhir'],
                    'hasData'
                ]
            ]);
    }
}
