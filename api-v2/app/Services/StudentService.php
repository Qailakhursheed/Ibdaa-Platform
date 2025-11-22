<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Pagination\LengthAwarePaginator;

class StudentService
{
    /**
     * Get all students with pagination and filters
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Student::query();

        // Apply filters
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $filters['per_page'] ?? 20;

        return $query->paginate($perPage);
    }

    /**
     * Get student by ID
     */
    public function getById(int $id): ?Student
    {
        return Student::with('courses')->find($id);
    }

    /**
     * Create new student
     */
    public function create(array $data): Student
    {
        return Student::create($data);
    }

    /**
     * Update student
     */
    public function update(int $id, array $data): bool
    {
        $student = Student::findOrFail($id);

        return $student->update($data);
    }

    /**
     * Delete student
     */
    public function delete(int $id): bool
    {
        $student = Student::findOrFail($id);

        return $student->delete();
    }

    /**
     * Get student statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => Student::count(),
            'active' => Student::where('status', 'active')->count(),
            'inactive' => Student::where('status', 'inactive')->count(),
            'male' => Student::where('gender', 'male')->count(),
            'female' => Student::where('gender', 'female')->count(),
        ];
    }
}
