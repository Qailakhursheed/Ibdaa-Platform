<?php

namespace Tests\Feature\API;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $trainer;
    protected string $managerToken;
    protected string $trainerToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::create([
            'full_name' => 'Test Manager',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'status' => 'active',
        ]);

        $this->trainer = User::create([
            'full_name' => 'Test Trainer',
            'email' => 'trainer@test.com',
            'password' => bcrypt('password'),
            'role' => 'trainer',
            'status' => 'active',
        ]);

        $this->managerToken = $this->manager->createToken('test')->plainTextToken;
        $this->trainerToken = $this->trainer->createToken('test')->plainTextToken;

        // Create test course
        Course::create([
            'course_name' => 'دورة البرمجة',
            'description' => 'دورة شاملة في البرمجة',
            'duration_days' => 30,
            'price' => 50000,
            'trainer_id' => $this->trainer->user_id,
            'status' => 'active',
        ]);
    }

    public function test_manager_can_list_courses()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson('/api/v1/courses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination',
            ]);
    }

    public function test_trainer_can_list_courses()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->trainerToken}")
            ->getJson('/api/v1/courses');

        $response->assertStatus(200);
    }

    public function test_manager_can_create_course()
    {
        $courseData = [
            'course_name' => 'دورة تصميم المواقع',
            'description' => 'دورة في تصميم المواقع',
            'duration_days' => 45,
            'duration_hours' => 90,
            'price' => 75000,
            'trainer_id' => $this->trainer->user_id,
            'start_date' => '2025-02-01',
            'end_date' => '2025-03-15',
            'max_students' => 20,
            'status' => 'pending',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson('/api/v1/courses', $courseData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'تم إضافة الدورة بنجاح',
            ]);

        $this->assertDatabaseHas('courses', [
            'course_name' => 'دورة تصميم المواقع',
        ]);
    }

    public function test_create_course_validation_fails_without_name()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson('/api/v1/courses', [
                'description' => 'Test',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR'],
            ]);
    }

    public function test_create_course_validation_fails_with_invalid_trainer()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson('/api/v1/courses', [
                'course_name' => 'Test Course',
                'trainer_id' => 99999, // Non-existent
            ]);

        $response->assertStatus(422);
    }

    public function test_create_course_validation_fails_with_invalid_dates()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->postJson('/api/v1/courses', [
                'course_name' => 'Test Course',
                'start_date' => '2025-03-01',
                'end_date' => '2025-02-01', // Before start date
            ]);

        $response->assertStatus(422);
    }

    public function test_manager_can_view_single_course()
    {
        $course = Course::first();

        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson("/api/v1/courses/{$course->course_id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'course_name' => 'دورة البرمجة',
                ],
            ]);
    }

    public function test_viewing_nonexistent_course_returns_404()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson('/api/v1/courses/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND'],
            ]);
    }

    public function test_manager_can_update_course()
    {
        $course = Course::first();

        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->putJson("/api/v1/courses/{$course->course_id}", [
                'course_name' => 'دورة البرمجة المتقدمة',
                'price' => 60000,
                'status' => 'completed',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم تحديث الدورة بنجاح',
            ]);

        $this->assertDatabaseHas('courses', [
            'course_id' => $course->course_id,
            'status' => 'completed',
        ]);
    }

    public function test_manager_can_delete_course()
    {
        $course = Course::create([
            'course_name' => 'To Delete',
            'description' => 'Test',
            'price' => 10000,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->deleteJson("/api/v1/courses/{$course->course_id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم حذف الدورة بنجاح',
            ]);

        $this->assertDatabaseMissing('courses', [
            'course_id' => $course->course_id,
        ]);
    }

    public function test_courses_list_supports_search()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson('/api/v1/courses?search=البرمجة');

        $response->assertStatus(200);
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_courses_list_supports_status_filter()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson('/api/v1/courses?status=active');

        $response->assertStatus(200);
    }

    public function test_courses_list_supports_trainer_filter()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson("/api/v1/courses?trainer_id={$this->trainer->user_id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        if (count($data) > 0) {
            $this->assertEquals($this->trainer->user_id, $data[0]['trainer_id']);
        }
    }

    public function test_course_includes_trainer_relationship()
    {
        $course = Course::first();

        $response = $this->withHeader('Authorization', "Bearer {$this->managerToken}")
            ->getJson("/api/v1/courses/{$course->course_id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['trainer'],
            ]);
    }
}
