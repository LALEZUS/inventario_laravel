<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_page_is_available(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Bienvenido de nuevo');
    }

    public function test_existing_user_can_log_in_with_username(): void
    {
        $user = User::create([
            'username' => 'test.admin',
            'password' => Hash::make('correct-password'),
            'full_name' => 'Administrador de prueba',
            'role' => 'admin',
        ]);

        $this->post('/login', [
            'username' => 'test.admin',
            'password' => 'correct-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_password_is_rejected(): void
    {
        User::create([
            'username' => 'test.support',
            'password' => Hash::make('correct-password'),
            'full_name' => 'Soporte de prueba',
            'role' => 'soporte',
        ]);

        $this->from('/login')->post('/login', [
            'username' => 'test.support',
            'password' => 'wrong-password',
        ])->assertRedirect('/login');

        $this->assertGuest();
    }
}
