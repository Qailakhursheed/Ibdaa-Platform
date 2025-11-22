<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CourseController extends Controller
{
    protected CourseService $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    /**
     * Display a listing of courses
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'trainer_id', 'sort_by', 'sort_order', 'per_page']);
        $courses = $this->courseService->getAll($filters);

        return response()->json([
            'success' => true,
            'data' => $courses->items(),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
                'last_page' => $courses->lastPage(),
            ],
        ]);
    }

    /**
     * Store a newly created course
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'course_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'duration_days' => 'nullable|integer',
                'duration_hours' => 'nullable|integer',
                'price' => 'nullable|numeric',
                'trainer_id' => 'nullable|exists:users,user_id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date',
                'max_students' => 'nullable|integer',
                'status' => 'nullable|in:pending,active,completed,cancelled',
            ]);

            $course = $this->courseService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الدورة بنجاح',
                'data' => $course,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'البيانات المدخلة غير صحيحة',
                    'details' => $e->errors(),
                ],
            ], 422);
        }
    }

    /**
     * Display the specified course
     */
    public function show(int $course): JsonResponse
    {
        $course = $this->courseService->getById($course);

        if (! $course) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'الدورة غير موجودة',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $course,
        ]);
    }

    /**
     * Update the specified course
     */
    public function update(Request $request, int $course): JsonResponse
    {
        try {
            $validated = $request->validate([
                'course_name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'duration_days' => 'nullable|integer',
                'duration_hours' => 'nullable|integer',
                'price' => 'nullable|numeric',
                'trainer_id' => 'nullable|exists:users,user_id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date',
                'max_students' => 'nullable|integer',
                'status' => 'nullable|in:pending,active,completed,cancelled',
            ]);

            $this->courseService->update($course, $validated);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الدورة بنجاح',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'البيانات المدخلة غير صحيحة',
                    'details' => $e->errors(),
                ],
            ], 422);
        }
    }

    /**
     * Remove the specified course
     */
    public function destroy(int $course): JsonResponse
    {
        try {
            $this->courseService->delete($course);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الدورة بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DELETE_ERROR',
                    'message' => 'فشل حذف الدورة',
                ],
            ], 500);
        }
    }
}
