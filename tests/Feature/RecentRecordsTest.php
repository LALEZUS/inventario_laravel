<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecentRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_record_lists_offer_and_accept_recent_sorting(): void
    {
        $user = User::create([
            'username' => 'recent_admin',
            'password' => Hash::make('password'),
            'full_name' => 'Administrador de recientes',
            'role' => 'admin',
        ]);

        $urls = [
            route('computers.index', ['recent' => 'updated']),
            route('cellphones.index', ['recent' => 'updated']),
            route('peripherals.index', ['recent' => 'updated']),
            route('printers.index', ['recent' => 'updated']),
            route('supplies.index', ['type' => 'inks', 'recent' => 'updated']),
            route('supplies.index', ['type' => 'toner', 'recent' => 'created']),
            route('employees.index', ['recent' => 'updated']),
            route('credentials.index', ['type' => 'accounts', 'recent' => 'updated']),
            route('credentials.index', ['type' => 'outlook', 'recent' => 'updated']),
            route('credentials.index', ['type' => 'licenses', 'recent' => 'created']),
            route('emails.index', ['type' => 'microsoft', 'recent' => 'updated']),
            route('emails.index', ['type' => 'windows', 'recent' => 'updated']),
            route('emails.index', ['type' => 'backups', 'recent' => 'created']),
            route('network.index', ['type' => 'devices', 'recent' => 'updated']),
            route('network.index', ['type' => 'watchguard', 'recent' => 'updated']),
            route('network.index', ['type' => 'networks', 'recent' => 'created']),
            route('tutorials.index', ['recent' => 'updated']),
            route('notes.index', ['recent' => 'updated']),
            route('files.index', ['recent' => 'updated']),
            route('gallery.index', ['recent' => 'updated']),
            route('users.index', ['recent' => 'updated']),
        ];

        foreach ($urls as $url) {
            $this->actingAs($user)->get($url)
                ->assertOk()
                ->assertSee('recent-filter', false)
                ->assertSee('Último actualizado');
        }
    }

    public function test_mobile_api_lists_accept_recent_sorting(): void
    {
        $user = User::create([
            'username' => 'recent_api_admin',
            'password' => Hash::make('password'),
            'full_name' => 'Administrador API de recientes',
            'role' => 'admin',
        ]);

        Sanctum::actingAs($user);

        $routes = [
            'api.v1.computadoras.index',
            'api.v1.celulares.index',
            'api.v1.perifericos.index',
            'api.v1.impresoras.index',
            'api.v1.empleados.index',
            'api.v1.consumibles.inks.index',
            'api.v1.consumibles.toner.index',
            'api.v1.red.dispositivos.index',
            'api.v1.red.watchguard.index',
            'api.v1.red.empresariales.index',
            'api.v1.archivos-generales.index',
            'api.v1.gallery.index',
            'api.v1.tutorials.index',
            'api.v1.notes.index',
            'api.v1.account-credentials.index',
            'api.v1.outlook-accounts.index',
        ];

        foreach (['created', 'updated'] as $mode) {
            foreach ($routes as $routeName) {
                $this->getJson(route($routeName, ['recent' => $mode]))
                    ->assertOk();
            }
        }
    }
}
