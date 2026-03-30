<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BootstrapsPassport;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use BootstrapsPassport;
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'API User',
            'email' => 'api-user@example.com',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'api-user@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'api-user@example.com',
        ]);
    }

    public function test_register_requires_required_fields(): void
    {
        $this->postJson('/api/auth/register', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_register_requires_a_unique_email(): void
    {
        User::factory()->create([
            'email' => 'api-user@example.com',
        ]);

        $this->postJson('/api/auth/register', [
            'name' => 'Duplicate User',
            'email' => 'api-user@example.com',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login_reach_me_and_logout(): void
    {
        $this->bootstrapPassport();

        $user = User::factory()->create([
            'email' => 'login-user@example.com',
            'password' => 'secret12345',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret12345',
        ]);

        $loginResponse->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_at', 'scopes', 'user']);

        $token = $loginResponse->json('access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('email', $user->email);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertTrue(
            (bool) DB::table('oauth_access_tokens')
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->value('revoked')
        );
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->bootstrapPassport();

        User::factory()->create([
            'email' => 'login-user@example.com',
            'password' => 'secret12345',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'login-user@example.com',
            'password' => 'wrong-secret',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'The provided credentials are incorrect.');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }
}
