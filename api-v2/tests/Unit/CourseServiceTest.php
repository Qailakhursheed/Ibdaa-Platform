<?php

namespace Tests\Unit;

use App\Models\Course;
use App\Models\User;
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseServiceTest extends TestCase
{
    use RefreshDatabase;

    private CourseService $service;
    private User $trainer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CourseService();

        // Create a trainer for tests
        $this->trainer = User::create([
            'full_name' => 'Test Trainer',
            'email' => 'trainer@test.com',
            'password' => bcrypt('password'),
            'role' => 'trainer',
            'status' => 'active',
        ]);
    }

    public function test_get_all_returns_paginated_courses()
    {
        // Create 15 courses
        for ($i = 1; $i <= 15; $i++) {
            Course::create([
                'course_name' => "Course {$i}",
                'duration_days' => 30,
                'price' => 1000,
                'trainer_id' => $this->trainer->user_id,
                'status' => 'active',
            ]);
        }

        $result = $this->service->getAll(['per_page' => 10]);

        $this->assertCount(10, $result->items());
        $this->assertEquals(15, $result->total());
        $this->assertEquals(2, $result->lastPage());
    }

    public function test_get_all_filters_by_search()
    {
        Course::create([
            'course_name' => 'PHP Programming',
            'duration_days' => 30,
            'price' => 1000,
            'trainer_id' => $this->trainer->user_id,
            'status' => 'active',
        ]);

        Course::create([
            'course_name' => 'JavaScript Basics',
            'duration_days' => 20,
            'price' => 800,
            'trainer_id' => $this->trainer->user_id,
            'status' => 'active',
        ]);

        $result = $this->service->getAll(['search' => 'PHP']);

        $this->assertCount(1, $result->items());
        $this->assertEquals('PHP Programming', $result->items()[0]->course_name);
    }

    public function test_get_all_filters_by_status()
    {
        Course::create([
            'course_name' => 'Active Course',
            'duration_days' => 30,
            'price' => 1000,
            'trainer_id' => $this->trainer->user_id,
            'status' => 'active',
        ]);

        Course::create([
            'course_name' => 'Completed Course',
            'duration_days' => 30,
            'price' => 1000,
            'trainer_id' => $this->trainer->user_id,
            'status' => 'completed',
        ]);

        $result = $this->service->getAll(['status' => 'active']);

        $this->assertCount(1, $result->items());
        $this->assertEquals('active', $result->items()[0]->status);
    }

    public function test_get_all_filters_by_trainer()
    {
        $anotherTrainer = User::create([
            'full_name' => 'Another Trainer',
            'email' => 'trainer2@test.com',
            'password' => bcrypt('password'),
            'role' => 'trainer',
            'status' => 'active',
        ]);

        Course::create([
            'course_name' => 'Course 1',
            'duration_days' => 30,
            'price' => 1000,
            'trainer_id' => $this->trainer->user_id,
            'status' => 'active',
        ]);

        Course::create([
            'course_name' => 'Course 2',
            'duration_days' => 30,
            'price' => 1000,
            'trainer_id' => $anotherTrainer->user_id,
            'status' => 'active',
        ]);

        $result = $this->service->getAll(['trainer_id' => $this->trainer->user_id]);

        $this->assertCount(1, $result->items());
        $this->assertEquals('Course 1', $result->items()[0]->course_name);
    }

    public function test_get_by_id_returns_course_with_trainer()
    {
        $course = Course::create([
            'course_name' => 'Test Course',
            'duration_days' => 30,
            'price' => 1000,
            'trainer_id' => $this->trainer->user_id,
            'status' => 'active',
        ]);

        $result = $this->service->getById($course->course_id);

        $this->assertNotNull($result);
        $this->assertEquals('Test Course', $result->course_name);
        $this->assertNotNull($result->trainer);
        $this->assertEquals('Test Trainer', $result->trainer->full_name);
    }

    public function test_get_by_id_returns_null_for_nonexistent_course()
    {
        $result = $this->service->getById(99999);

        $this->assertNull($result);
    }

    public function test_create_creates_new_course()
    {
        $data = [
            'course_name' => 'New Course',
            'description' => 'Test description',
            'duration_days' => 30,
            'duration_hours' => 120,
            'price' => 1500,
            'trainer_id' => $this->trainer->user_id,
            'start_date' => '2025-02-01',
            'end_date' => '2025-03-01',
            'max_students' => 20,
            'status' => 'active',
        ];

        $course = $this->service->create($data);

        $this->assertNotNull($course);
        $this->assertEquals('New Course', $course->course_name);
        $this->assertEquals(1500, $course->price);
        $this->assertDatabaseHas('courses', ['course_name' => 'New Course']);
    }

    public function test_update_updates_existing_course()
    {
        $course = Course::create([
            'course_name' => 'Original Name',
            'duration_days' => 30,
            'price' => 1000,
            'trainer_id' => $this->trainer->user_id,
            'status' => 'active',
        ]);

        $updated = $this->service->update($course->course_id, [
            'course_name' => 'Updated Name',
            'price' => 2000,
        ]);

        $this->assertTrue($updated);
        $course->refresh();
        $this->assertEquals('Updated Name', $course->course_name);
        $this->assertEquals(2000, $course->price);
    }

    public function test_update_returns_false_for_nonexistent_course()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->update(99999, ['course_name' => 'Test']);
    }

    public function test_delete_deletes_course()
    {
        $course = Course::create([
            'course_name' => 'To Delete',
            'duration_days' => 30,
            'price' => 1000,
            'trainer_id' => $this->trainer->user_id,
            'status' => 'active',
        ]);

        $result = $this->service->delete($course->course_id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('courses', ['course_id' => $course->course_id]);
    }

    public function test_delete_returns_false_for_nonexistent_course()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->delete(99999);
    }

    public function test_get_statistics_returns_correct_counts()
    {
        Course::create([
            'course_name' => 'C1',
            'duration_days' => 30,
            'price' => 1000,
            'trainer_id' => $this->trainer->user_id,
            'status' => 'active',
        ]);

        Course::create([
            'course_name' => 'C2',
            'duration_days' => 30,
            'price' => 1000,
            'trainer_id' => $this->trainer->user_id,
            'status' => 'active',
        ]);

        Course::create([
            'course_name' => 'C3',
            'duration_days' => 30,
            'price' => 1000,
            'trainer_id' => $this->trainer->user_id,
            'status' => 'completed',
        ]);

        $stats = $this->service->getStatistics();

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['active']);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(0, $stats['cancelled'] ?? 0);
    }
}
