<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Pagination\LengthAwarePaginator;

class CourseService
{
    /**
     * Get all courses with pagination and filters
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = Course::with('trainer');

        // Apply filters
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('course_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['trainer_id'])) {
            $query->where('trainer_id', $filters['trainer_id']);
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
     * Get course by ID
     */
    public function getById(int $id): ?Course
    {
        return Course::with(['trainer', 'students'])->find($id);
    }

    /**
     * Create new course
     */
    public function create(array $data): Course
    {
        return Course::create($data);
    }

    /**
     * Update course
     */
    public function update(int $id, array $data): bool
    {
        $course = Course::findOrFail($id);

        return $course->update($data);
    }

    /**
     * Delete course
     */
    public function delete(int $id): bool
    {
        $course = Course::findOrFail($id);

        return $course->delete();
    }

    /**
     * Get course statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => Course::count(),
            'active' => Course::where('status', 'active')->count(),
            'pending' => Course::where('status', 'pending')->count(),
            'completed' => Course::where('status', 'completed')->count(),
        ];
    }
}
