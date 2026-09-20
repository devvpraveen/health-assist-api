<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_issues_token(): void
    {
        $user = User::factory()->create([
            'email' => 'api@healthassist.test',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => ['id', 'uuid', 'email', 'name'],
            ]);

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login',
            'actor_user_id' => $user->id,
        ]);
    }

    public function test_me_works_with_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.uuid', $user->uuid);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('message', 'Logged out');

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.logout',
            'actor_user_id' => $user->id,
        ]);
    }

    public function test_invalid_password_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'badpass@healthassist.test',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_creates_standalone_user(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Patient User',
            'email' => 'patient@healthassist.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'patient@healthassist.test')
            ->assertJsonPath('data.tenant_id', null);

        $this->assertDatabaseHas('users', [
            'email' => 'patient@healthassist.test',
            'tenant_id' => null,
        ]);
    }
}
