<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_via_api_and_receive_sanctum_bearer_token(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'password' => Hash::make('secret123'),
            'full_name' => 'Usuario Test',
            'role' => 'soporte',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'testuser',
            'password' => 'secret123',
            'device_name' => 'Flutter Mobile Test',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.username', 'testuser')
            ->assertJsonPath('data.user.role', 'soporte');

        $this->assertNotNull($response->json('data.token'));
        // Verify database holds hashed token, plainTextToken was returned
        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'Flutter Mobile Test',
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_api_login_fails_with_invalid_credentials(): void
    {
        User::create([
            'username' => 'testuser',
            'password' => Hash::make('secret123'),
            'role' => 'consulta',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'testuser',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['username']]);
    }

    public function test_authenticated_user_can_fetch_profile_and_logout(): void
    {
        $user = User::create([
            'username' => 'api_user',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $token = $user->createToken('TestDevice')->plainTextToken;

        // Fetch profile
        $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(200)
            ->assertJsonPath('data.username', 'api_user')
            ->assertJsonPath('data.role', 'admin');

        // Logout
        $logoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }
}
