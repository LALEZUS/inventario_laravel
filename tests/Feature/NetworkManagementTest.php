<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\EnterpriseNetwork;
use App\Models\NetworkDevice;
use App\Models\User;
use App\Models\WatchguardUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NetworkManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_can_create_and_find_network_device_immediately(): void
    {
        $response = $this->actingAs($this->user('soporte'))->post(route('network-devices.store'), [
            'device_name' => 'Switch Core Zenith', 'device_type' => 'Switch', 'brand' => 'Ubiquiti',
            'ip_address' => '192.168.15.250', 'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'location' => 'Site', 'status' => 'Activo',
        ]);
        $device = NetworkDevice::firstOrFail();
        $response->assertSessionHasNoErrors()->assertRedirect(route('network-devices.show', $device));
        $this->get(route('network.index', ['search' => '192.168.15.250']))->assertOk()->assertSee('Switch Core Zenith');
        $this->getJson(route('search.suggestions', ['q' => 'Zenith']))
            ->assertOk()->assertJsonPath('results.0.url', route('network-devices.show', $device));
        $this->assertDatabaseHas('audit_logs', ['entity' => 'network_devices', 'action' => 'create']);
    }

    public function test_watchguard_password_is_hidden_from_consultation_and_protected_in_audit(): void
    {
        $support = $this->user('soporte');
        $this->actingAs($support)->post(route('watchguard-users.store'), [
            'username' => 'vpn-zenith', 'password' => 'Secret-WG-2026',
            'assigned_to' => 'Usuario VPN', 'area' => 'Sistemas', 'ip' => '192.168.15.33',
        ])->assertSessionHasNoErrors();
        $user = WatchguardUser::firstOrFail();
        $audit = AuditLog::where('entity', 'watchguard_users')->firstOrFail();
        $this->assertSame('[PROTECTED]', $audit->after_data['password']);

        $this->actingAs($this->user('consulta'))->get(route('watchguard-users.show', $user))
            ->assertOk()->assertSee('vpn-zenith')->assertDontSee('Secret-WG-2026');
        $this->get(route('network.index', ['type' => 'watchguard', 'search' => 'Secret-WG-2026']))
            ->assertOk()->assertDontSee('vpn-zenith');
        $this->getJson(route('search.suggestions', ['q' => 'Secret-WG-2026']))
            ->assertOk()->assertJsonCount(0, 'results');
    }

    public function test_blank_password_update_preserves_enterprise_network_secret(): void
    {
        $network = EnterpriseNetwork::create([
            'network_name' => 'TG-Operaciones', 'password' => 'Wifi-Original-2026',
            'location' => 'V3', 'encryption' => 'WPA3',
        ]);
        $this->actingAs($this->user('soporte'))->put(route('enterprise-networks.update', $network), [
            'network_name' => 'TG-Operaciones', 'password' => '',
            'location' => 'V2 y V3', 'encryption' => 'WPA3',
        ])->assertSessionHasNoErrors()->assertRedirect(route('enterprise-networks.show', $network));

        $this->assertSame('Wifi-Original-2026', $network->fresh()->password);
        $this->assertSame('V2 y V3', $network->fresh()->location);
        $this->actingAs($this->user('consulta'))->get(route('enterprise-networks.show', $network))
            ->assertOk()->assertDontSee('Wifi-Original-2026');
    }

    public function test_admin_can_reveal_enterprise_network_password_but_consultation_cannot(): void
    {
        $network = EnterpriseNetwork::create([
            'network_name' => 'TG-Administracion',
            'password' => 'Wifi-Visible-Solo-Admin',
        ]);

        $this->actingAs($this->user('admin'))
            ->get(route('enterprise-networks.show', $network))
            ->assertOk()
            ->assertSee('Contrase&ntilde;a de la red', false)
            ->assertSee('data-secret-field="password"', false);

        $secretResponse = $this->getJson(route('enterprise-networks.secrets', $network));
        $secretResponse->assertOk()->assertJsonPath('password', 'Wifi-Visible-Solo-Admin');
        $this->assertStringContainsString('no-store', (string) $secretResponse->headers->get('Cache-Control'));

        $this->actingAs($this->user('consulta'))
            ->getJson(route('enterprise-networks.secrets', $network))
            ->assertForbidden();
    }

    public function test_consultation_cannot_modify_network_records(): void
    {
        $device = NetworkDevice::create(['device_name' => 'Router protegido', 'status' => 'Activo']);
        $watchguard = WatchguardUser::create(['username' => 'protegido', 'password' => 'secret']);
        $network = EnterpriseNetwork::create(['network_name' => 'SSID protegido']);
        $this->actingAs($this->user('consulta'));

        $this->get(route('network-devices.create'))->assertForbidden();
        $this->delete(route('network-devices.destroy', $device))->assertForbidden();
        $this->get(route('watchguard-users.edit', $watchguard))->assertForbidden();
        $this->delete(route('enterprise-networks.destroy', $network))->assertForbidden();
    }

    private function user(string $role): User
    {
        return User::create([
            'username' => 'network_'.$role.'_'.User::count(),
            'password' => Hash::make('password'),
            'full_name' => 'Usuario '.$role,
            'role' => $role,
        ]);
    }
}
