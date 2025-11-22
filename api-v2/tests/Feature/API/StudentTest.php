<?php

namespace Tests\Feature\API;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $student;
    protected string $managerToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->manager = User::create([
            'full_name' => 'Test Manager',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'status' => 'active',
        ]);

        $this->student = User::create([
            'full_name' => 'Test Student User',
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
            'status' => 'active',
        ]);

        $this->managerToken = $this->manager->createToken('test')->plainTextToken;

        // Create test students
        Student::create([
            'full_name' => 'أحمد محمد',
            'email' => 'ahmad@example.com',
            'phone' => '967777123456',
            'status' => 'active',
        ]);
    }

    public function test_manager_can_list_students()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson('/api/v1/students');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_student_role_cannot_access_students_list()
    {
        $studentToken = $this->student->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $studentToken")
            ->getJson('/api/v1/students');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'FORBIDDEN'],
            ]);
    }

    public function test_manager_can_create_student()
    {
        $studentData = [
            'full_name' => 'محمد علي',
            'email' => 'mohammed@example.com',
            'phone' => '967777999888',
            'gender' => 'male',
            'status' => 'active',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson('/api/v1/students', $studentData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'تم إضافة الطالب بنجاح',
            ]);

        $this->assertDatabaseHas('students', [
            'email' => 'mohammed@example.com',
        ]);
    }

    public function test_create_student_validation_fails_with_invalid_email()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson('/api/v1/students', [
                'full_name' => 'Test',
                'email' => 'invalid-email',
                'phone' => '967777123456',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR'],
            ]);
    }

    public function test_create_student_validation_fails_with_duplicate_email()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson('/api/v1/students', [
                'full_name' => 'Test',
                'email' => 'ahmad@example.com', // Already exists
                'phone' => '967777123456',
            ]);

        $response->assertStatus(422);
    }

    public function test_manager_can_view_single_student()
    {
        $student = Student::first();

        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson("/api/v1/students/{$student->student_id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email' => 'ahmad@example.com',
                ],
            ]);
    }

    public function test_viewing_nonexistent_student_returns_404()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson('/api/v1/students/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND'],
            ]);
    }

    public function test_manager_can_update_student()
    {
        $student = Student::first();

        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->putJson("/api/v1/students/{$student->student_id}", [
                'full_name' => 'أحمد محمد المحدث',
                'status' => 'inactive',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم تحديث بيانات الطالب بنجاح',
            ]);

        $this->assertDatabaseHas('students', [
            'student_id' => $student->student_id,
            'status' => 'inactive',
        ]);
    }

    public function test_manager_can_delete_student()
    {
        $student = Student::create([
            'full_name' => 'To Delete',
            'email' => 'delete@example.com',
            'phone' => '967777111222',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->deleteJson("/api/v1/students/{$student->student_id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم حذف الطالب بنجاح',
            ]);

        $this->assertDatabaseMissing('students', [
            'student_id' => $student->student_id,
        ]);
    }

    public function test_students_list_supports_search()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson('/api/v1/students?search=أحمد');

        $response->assertStatus(200);
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_students_list_supports_status_filter()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson('/api/v1/students?status=active');

        $response->assertStatus(200);
    }

    public function test_students_list_supports_pagination()
    {
        // Create more students
        for ($i = 1; $i <= 25; $i++) {
            Student::create([
                'full_name' => "Student $i",
                'email' => "student$i@example.com",
                'phone' => "96777712345$i",
            ]);
        }

        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson('/api/v1/students?per_page=10');

        $response->assertStatus(200);
        $this->assertEquals(10, count($response->json('data')));
        $this->assertArrayHasKey('pagination', $response->json());
    }
}
