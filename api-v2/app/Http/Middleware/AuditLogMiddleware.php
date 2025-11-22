<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    /**
     * Sensitive actions that should be logged
     */
    private array $sensitiveActions = [
        'DELETE',  // Any delete operation
        'POST',    // Create operations
        'PUT',     // Update operations
        'PATCH',   // Partial update operations
    ];

    /**
     * Routes that should always be logged
     */
    private array $criticalRoutes = [
        'students.destroy',
        'courses.destroy',
        'users.destroy',
        'enrollments.destroy',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log for authenticated users
        if (Auth::check() && $this->shouldLog($request, $response)) {
            $this->logRequest($request, $response);
        }

        return $response;
    }

    /**
     * Determine if this request should be logged
     */
    private function shouldLog(Request $request, Response $response): bool
    {
        // Log all sensitive HTTP methods
        if (in_array($request->method(), $this->sensitiveActions)) {
            return true;
        }

        // Log critical routes
        $routeName = $request->route()?->getName();
        if ($routeName && in_array($routeName, $this->criticalRoutes)) {
            return true;
        }

        // Log failed attempts (4xx, 5xx errors)
        if ($response->getStatusCode() >= 400) {
            return true;
        }

        return false;
    }

    /**
     * Log the request
     */
    private function logRequest(Request $request, Response $response): void
    {
        $user = Auth::user();
        $method = $request->method();
        $routeName = $request->route()?->getName() ?? 'unknown';
        $statusCode = $response->getStatusCode();

        // Determine action and description
        [$action, $description, $severity] = $this->determineAction($request, $response);

        // Extract model info from route
        $modelType = $this->extractModelType($routeName);
        $modelId = $this->extractModelId($request);

        // Get old and new values for updates
        $oldValues = null;
        $newValues = null;

        if ($method === 'PUT' || $method === 'PATCH') {
            $newValues = $request->except(['password', '_token', '_method']);
        } elseif ($method === 'POST') {
            $newValues = $request->except(['password', '_token']);
        }

        AuditLog::create([
            'user_id' => $user->id,
            'user_name' => $user->full_name,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'description' => $description,
            'http_method' => $method,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => [
                'route' => $routeName,
                'status_code' => $statusCode,
            ],
            'severity' => $severity,
        ]);
    }

    /**
     * Determine the action type and description
     */
    private function determineAction(Request $request, Response $response): array
    {
        $method = $request->method();
        $statusCode = $response->getStatusCode();
        $routeName = $request->route()?->getName() ?? '';

        // Failed attempts
        if ($statusCode >= 400) {
            $severity = $statusCode >= 500 ? 'critical' : 'high';
            return ['failed_attempt', "محاولة فاشلة: {$method} {$request->path()}", $severity];
        }

        // Successful operations
        $severity = 'medium';
        
        if (str_contains($routeName, 'destroy') || $method === 'DELETE') {
            $severity = 'critical';
            return ['delete', 'حذف سجل', $severity];
        }

        if ($method === 'POST') {
            $severity = 'high';
            return ['create', 'إضافة سجل جديد', $severity];
        }

        if ($method === 'PUT' || $method === 'PATCH') {
            $severity = 'high';
            return ['update', 'تحديث سجل', $severity];
        }

        return ['access', 'الوصول إلى مورد', 'low'];
    }

    /**
     * Extract model type from route name
     */
    private function extractModelType(string $routeName): ?string
    {
        if (str_contains($routeName, 'students')) {
            return 'Student';
        }
        if (str_contains($routeName, 'courses')) {
            return 'Course';
        }
        if (str_contains($routeName, 'users')) {
            return 'User';
        }
        if (str_contains($routeName, 'enrollments')) {
            return 'Enrollment';
        }

        return null;
    }

    /**
     * Extract model ID from request
     */
    private function extractModelId(Request $request): ?int
    {
        // Try to get from route parameters
        $route = $request->route();
        if ($route) {
            foreach (['student', 'course', 'user', 'enrollment', 'id'] as $param) {
                $value = $route->parameter($param);
                if ($value && is_numeric($value)) {
                    return (int) $value;
                }
            }
        }

        return null;
    }
}
