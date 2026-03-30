<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ApiResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
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
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'api-user@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'api-user@example.com',
        ]);
    }

    public function test_user_can_login_and_reach_me_endpoint(): void
    {
        $this->bootstrapPassport();

        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ]);

        $loginResponse->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'user']);

        $token = $loginResponse->json('access_token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('email', $user->email);
    }

    public function test_forgot_password_sends_api_reset_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'forgot@example.com',
        ]);

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertOk();

        Notification::assertSentTo($user, ApiResetPasswordNotification::class);
    }

    public function test_user_can_reset_password_with_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-secret123',
            'password_confirmation' => 'new-secret123',
        ]);

        $response->assertOk();

        $this->assertTrue(Hash::check('new-secret123', $user->fresh()->password));
    }
}
