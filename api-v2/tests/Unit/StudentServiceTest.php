<?php

namespace Tests\Unit;

use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentServiceTest extends TestCase
{
    use RefreshDatabase;

    private StudentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StudentService();
    }

    public function test_get_all_returns_paginated_students()
    {
        // Create 15 students
        for ($i = 1; $i <= 15; $i++) {
            Student::create([
                'full_name' => "Student {$i}",
                'email' => "student{$i}@test.com",
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
        Student::create([
            'full_name' => 'Ahmed Ali',
            'email' => 'ahmed@test.com',
            'status' => 'active',
        ]);

        Student::create([
            'full_name' => 'Sara Mohamed',
            'email' => 'sara@test.com',
            'status' => 'active',
        ]);

        $result = $this->service->getAll(['search' => 'Ahmed']);

        $this->assertCount(1, $result->items());
        $this->assertEquals('Ahmed Ali', $result->items()[0]->full_name);
    }

    public function test_get_all_filters_by_status()
    {
        Student::create([
            'full_name' => 'Active Student',
            'email' => 'active@test.com',
            'status' => 'active',
        ]);

        Student::create([
            'full_name' => 'Inactive Student',
            'email' => 'inactive@test.com',
            'status' => 'inactive',
        ]);

        $result = $this->service->getAll(['status' => 'active']);

        $this->assertCount(1, $result->items());
        $this->assertEquals('active', $result->items()[0]->status);
    }

    public function test_get_by_id_returns_student()
    {
        $student = Student::create([
            'full_name' => 'Test Student',
            'email' => 'test@test.com',
            'status' => 'active',
        ]);

        $result = $this->service->getById($student->student_id);

        $this->assertNotNull($result);
        $this->assertEquals('Test Student', $result->full_name);
    }

    public function test_get_by_id_returns_null_for_nonexistent_student()
    {
        $result = $this->service->getById(99999);

        $this->assertNull($result);
    }

    public function test_create_creates_new_student()
    {
        $data = [
            'full_name' => 'New Student',
            'email' => 'new@test.com',
            'phone' => '1234567890',
            'date_of_birth' => '2000-01-01',
            'gender' => 'male',
            'status' => 'active',
        ];

        $student = $this->service->create($data);

        $this->assertNotNull($student);
        $this->assertEquals('New Student', $student->full_name);
        $this->assertEquals('new@test.com', $student->email);
        $this->assertDatabaseHas('students', ['email' => 'new@test.com']);
    }

    public function test_update_updates_existing_student()
    {
        $student = Student::create([
            'full_name' => 'Original Name',
            'email' => 'original@test.com',
            'status' => 'active',
        ]);

        $updated = $this->service->update($student->student_id, [
            'full_name' => 'Updated Name',
            'phone' => '9876543210',
        ]);

        $this->assertTrue($updated);
        $student->refresh();
        $this->assertEquals('Updated Name', $student->full_name);
        $this->assertEquals('9876543210', $student->phone);
    }

    public function test_update_returns_false_for_nonexistent_student()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->update(99999, ['full_name' => 'Test']);
    }

    public function test_delete_deletes_student()
    {
        $student = Student::create([
            'full_name' => 'To Delete',
            'email' => 'delete@test.com',
            'status' => 'active',
        ]);

        $result = $this->service->delete($student->student_id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('students', ['student_id' => $student->student_id]);
    }

    public function test_delete_returns_false_for_nonexistent_student()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->delete(99999);
    }

    public function test_get_statistics_returns_correct_counts()
    {
        Student::create(['full_name' => 'S1', 'email' => 's1@test.com', 'status' => 'active']);
        Student::create(['full_name' => 'S2', 'email' => 's2@test.com', 'status' => 'active']);
        Student::create(['full_name' => 'S3', 'email' => 's3@test.com', 'status' => 'inactive']);

        $stats = $this->service->getStatistics();

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['active']);
        $this->assertEquals(1, $stats['inactive']);
    }
}
