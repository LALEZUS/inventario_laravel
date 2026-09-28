<?php

namespace Tests\Feature;

use App\Models\CalendarReminder;
use App\Models\HardwareAsset;
use App\Models\Ink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_live_agenda_and_inventory_alerts(): void
    {
        Carbon::setTestNow('2026-08-14 10:00:00');

        $user = User::create([
            'username' => 'dashboard_support',
            'password' => Hash::make('password'),
            'full_name' => 'Soporte Dashboard',
            'role' => 'soporte',
        ]);
        $computer = HardwareAsset::create([
            'name' => 'Equipo de prueba sin asignar',
            'status' => 'Disponible',
        ]);
        CalendarReminder::create([
            'event_date' => '2026-08-20',
            'title' => 'Mantenimiento preventivo',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Agenda Global')
            ->assertSee('Mantenimiento preventivo')
            ->assertSee('Equipo sin usuario')
            ->assertSee(route('computers.show', $computer), false)
            ->assertSee('datetime="2026-08-14"', false)
            ->assertSee('dashboard-calendar', false);

        $this->assertSame(42, substr_count($response->getContent(), 'class="calendar-cell'));
    }

    public function test_dashboard_alerts_when_only_one_ink_remains(): void
    {
        $user = User::create([
            'username' => 'ink_alert_admin',
            'password' => Hash::make('password'),
            'full_name' => 'Administrador de tintas',
            'role' => 'admin',
        ]);

        Ink::create([
            'brand' => 'Marca Alerta Uno',
            'model' => 'TINTA-UNO',
            'color' => 'Negro',
            'type' => 'Botella',
            'quantity' => 1,
            'status' => 'Bajo',
        ]);
        Ink::create([
            'brand' => 'Marca Sin Alerta Dos',
            'model' => 'TINTA-DOS',
            'color' => 'Cian',
            'type' => 'Botella',
            'quantity' => 2,
            'status' => 'Disponible',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tinta con existencia baja')
            ->assertSee('Marca Alerta Uno TINTA-UNO Negro')
            ->assertDontSee('Marca Sin Alerta Dos TINTA-DOS Cian');
    }
}
