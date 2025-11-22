<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()->orderBy('created_at', 'desc');

        // Filter by user
        if ($request->has('user_id')) {
            $query->byUser($request->user_id);
        }

        // Filter by action
        if ($request->has('action')) {
            $query->byAction($request->action);
        }

        // Filter by model
        if ($request->has('model_type')) {
            $query->byModel($request->model_type, $request->model_id);
        }

        // Filter by severity
        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Search in description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('user_email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 20);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
        ]);
    }

    /**
     * Display the specified audit log
     */
    public function show(int $id): JsonResponse
    {
        $log = AuditLog::find($id);

        if (!$log) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'سجل التدقيق غير موجود',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    /**
     * Get audit log statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());

        $query = AuditLog::dateRange($startDate, $endDate);

        $stats = [
            'total_logs' => $query->count(),
            'by_action' => $query->clone()
                ->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->pluck('count', 'action'),
            'by_user_role' => $query->clone()
                ->selectRaw('user_role, COUNT(*) as count')
                ->groupBy('user_role')
                ->pluck('count', 'user_role'),
            'by_severity' => $query->clone()
                ->selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity'),
            'critical_actions' => $query->clone()->where('severity', 'critical')->count(),
            'failed_attempts' => $query->clone()->where('action', 'failed_attempt')->count(),
            'top_users' => $query->clone()
                ->selectRaw('user_name, user_email, COUNT(*) as action_count')
                ->whereNotNull('user_name')
                ->groupBy('user_name', 'user_email')
                ->orderByDesc('action_count')
                ->limit(10)
                ->get(),
            'recent_critical' => AuditLog::critical()
                ->dateRange($startDate, $endDate)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get user activity timeline
     */
    public function userActivity(int $userId): JsonResponse
    {
        $logs = AuditLog::byUser($userId)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Get model history
     */
    public function modelHistory(Request $request): JsonResponse
    {
        $modelType = $request->model_type;
        $modelId = $request->model_id;

        if (!$modelType || !$modelId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'يجب تحديد نوع النموذج والمعرف',
                ],
            ], 422);
        }

        $logs = AuditLog::byModel($modelType, $modelId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
