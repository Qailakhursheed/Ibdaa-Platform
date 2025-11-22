<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    protected StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * Display a listing of students
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'gender', 'sort_by', 'sort_order', 'per_page']);
        $students = $this->studentService->getAll($filters);

        return response()->json([
            'success' => true,
            'data' => $students->items(),
            'pagination' => [
                'current_page' => $students->currentPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
                'last_page' => $students->lastPage(),
            ],
            'links' => [
                'first' => $students->url(1),
                'last' => $students->url($students->lastPage()),
                'prev' => $students->previousPageUrl(),
                'next' => $students->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Store a newly created student
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'full_name' => 'required|string|max:255',
                'email' => 'required|email|unique:students,email',
                'phone' => 'required|string|max:20',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female',
                'address' => 'nullable|string',
                'status' => 'nullable|in:active,inactive,suspended',
            ]);

            $student = $this->studentService->create($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الطالب بنجاح',
                'data' => $student,
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
     * Display the specified student
     */
    public function show(int $student): JsonResponse
    {
        $student = $this->studentService->getById($student);

        if (! $student) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'الطالب غير موجود',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $student,
        ]);
    }

    /**
     * Update the specified student
     */
    public function update(Request $request, int $student): JsonResponse
    {
        try {
            $validated = $request->validate([
                'full_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:students,email,' . $student . ',student_id',
                'phone' => 'sometimes|string|max:20',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female',
                'address' => 'nullable|string',
                'status' => 'nullable|in:active,inactive,suspended',
            ]);

            $this->studentService->update($student, $validated);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات الطالب بنجاح',
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
     * Remove the specified student
     */
    public function destroy(int $student): JsonResponse
    {
        try {
            $this->studentService->delete($student);

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الطالب بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DELETE_ERROR',
                    'message' => 'فشل حذف الطالب',
                ],
            ], 500);
        }
    }
}
