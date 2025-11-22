<?php

namespace Tests\Unit;

use App\Http\Middleware\CheckRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class CheckRoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithRole(string $role): User
    {
        return User::create([
            'full_name' => 'Test User',
            'email' => "test_{$role}@test.com",
            'password' => bcrypt('password'),
            'role' => $role,
            'status' => 'active',
        ]);
    }

    public function test_middleware_allows_user_with_correct_role()
    {
        $user = $this->createUserWithRole('manager');

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new CheckRole();
        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, 'manager');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    public function test_middleware_allows_user_with_one_of_multiple_allowed_roles()
    {
        $user = $this->createUserWithRole('technical');

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new CheckRole();
        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, 'manager', 'technical');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_middleware_denies_user_without_authentication()
    {
        $request = Request::create('/test', 'GET');
        // No user authenticated
        $request->setUserResolver(fn () => null);

        $middleware = new CheckRole();
        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, 'manager');

        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('UNAUTHORIZED', $data['error']['code']);
    }

    public function test_middleware_denies_user_with_wrong_role()
    {
        $user = $this->createUserWithRole('student');

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new CheckRole();
        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, 'manager', 'technical');

        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('FORBIDDEN', $data['error']['code']);
    }

    public function test_middleware_allows_trainer_role()
    {
        $user = $this->createUserWithRole('trainer');

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new CheckRole();
        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, 'trainer', 'manager');

        $this->assertEquals(200, $response->getStatusCode());
    }
}
