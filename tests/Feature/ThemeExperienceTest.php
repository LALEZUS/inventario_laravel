<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ThemeExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_loads_the_saved_appearance_before_rendering(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('total-ground-inventory-appearance', false)
            ->assertSee('class="theme-toggle"', false)
            ->assertSee('Original oscuro')
            ->assertSee('Material 3 oscuro');
    }

    public function test_authenticated_layout_has_an_accessible_theme_control(): void
    {
        $user = User::create([
            'username' => 'theme_admin',
            'password' => Hash::make('password'),
            'full_name' => 'Administrador Tema',
            'role' => 'admin',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aria-haspopup="menu"', false)
            ->assertSee('role="menuitemradio"', false)
            ->assertSee('total-ground-inventory-appearance', false);
    }
}
