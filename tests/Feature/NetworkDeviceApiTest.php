<?php

namespace Tests\Feature;

use App\Models\NetworkDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NetworkDeviceApiTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, ?string $username = null): User
    {
        return User::create([
            'username' => $username ?? ('user_'.$role.'_'.User::count()),
            'password' => Hash::make('password'),
            'full_name' => 'Usuario '.$role,
            'role' => $role,
        ]);
    }

    public function test_can_search_devices_by_name_ip_mac_and_filter_by_status(): void
    {
        $admin = $this->user('admin');

        $d1 = NetworkDevice::create([
            'device_name' => 'Switch Core Zenith',
            'device_type' => 'Switch',
            'brand' => 'Ubiquiti',
            'ip_address' => '192.168.15.250',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'location' => 'Site Principal',
            'status' => 'Activo',
        ]);

        $d2 = NetworkDevice::create([
            'device_name' => 'Router Backup',
            'device_type' => 'Router',
            'brand' => 'Cisco',
            'ip_address' => '10.0.0.1',
            'mac_address' => '11:22:33:44:55:66',
            'location' => 'Site Secundario',
            'status' => 'Mantenimiento',
        ]);

        // Search by name
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/red/dispositivos?search=Zenith')
            ->assertStatus(200)
            ->assertJsonPath('data.0.id', $d1->id);

        // Search by IP
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/red/dispositivos?search=10.0.0.1')
            ->assertStatus(200)
            ->assertJsonPath('data.0.id', $d2->id);

        // Search by MAC
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/red/dispositivos?search=AA:BB:CC:DD:EE:FF')
            ->assertStatus(200)
            ->assertJsonPath('data.0.id', $d1->id);

        // Filter by status
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/red/dispositivos?status=Mantenimiento')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $d2->id);
    }

    public function test_valid_and_invalid_ip_and_mac_address_validation(): void
    {
        $support = $this->user('soporte');

        // Invalid IP
        $this->actingAs($support, 'sanctum')
            ->postJson('/api/v1/red/dispositivos', [
                'device_name' => 'AP Invalido',
                'ip_address' => '999.999.999.999',
                'status' => 'Activo',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ip_address']);

        // Invalid MAC
        $this->actingAs($support, 'sanctum')
            ->postJson('/api/v1/red/dispositivos', [
                'device_name' => 'AP MAC Invalida',
                'mac_address' => 'INVALID-MAC-ADDRESS',
                'status' => 'Activo',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['mac_address']);

        // Valid IP and MAC
        $this->actingAs($support, 'sanctum')
            ->postJson('/api/v1/red/dispositivos', [
                'device_name' => 'AP Oficinas',
                'ip_address' => '192.168.15.10',
                'mac_address' => '00:11:22:33:44:55',
                'status' => 'Activo',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.device_name', 'AP Oficinas');
    }

    public function test_support_user_can_create_and_update_device(): void
    {
        $support = $this->user('soporte');
        $device = NetworkDevice::create([
            'device_name' => 'Switch Piso 1',
            'status' => 'Activo',
        ]);

        $this->actingAs($support, 'sanctum')
            ->putJson("/api/v1/red/dispositivos/{$device->id}", [
                'device_name' => 'Switch Piso 1 Editado',
                'status' => 'Mantenimiento',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.device_name', 'Switch Piso 1 Editado');

        $this->assertDatabaseHas('network_devices', [
            'id' => $device->id,
            'device_name' => 'Switch Piso 1 Editado',
        ]);
    }

    public function test_support_cannot_delete_device_but_admin_can(): void
    {
        $support = $this->user('soporte');
        $admin = $this->user('admin');
        $device = NetworkDevice::create(['device_name' => 'Firewall Antiguo', 'status' => 'Inactivo']);

        // Support gets 403
        $this->actingAs($support, 'sanctum')
            ->deleteJson("/api/v1/red/dispositivos/{$device->id}")
            ->assertStatus(403);

        // Admin gets 200
        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/red/dispositivos/{$device->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('network_devices', ['id' => $device->id]);
    }

    public function test_view_audit_restricted_to_admin(): void
    {
        $consultation = $this->user('consulta');
        $support = $this->user('soporte');
        $admin = $this->user('admin');

        $device = NetworkDevice::create(['device_name' => 'Dispositivo Auditable', 'status' => 'Activo']);

        $this->actingAs($consultation, 'sanctum')->getJson("/api/v1/red/dispositivos/{$device->id}/bitacora")->assertStatus(403);
        $this->actingAs($support, 'sanctum')->getJson("/api/v1/red/dispositivos/{$device->id}/bitacora")->assertStatus(403);
        $this->actingAs($admin, 'sanctum')->getJson("/api/v1/red/dispositivos/{$device->id}/bitacora")->assertStatus(200);
    }

    public function test_resource_contains_only_whitelisted_fields_no_secrets(): void
    {
        $admin = $this->user('admin');
        $device = NetworkDevice::create([
            'device_name' => 'Switch Core',
            'device_type' => 'Switch',
            'ip_address' => '192.168.1.1',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'location' => 'Rack A',
            'brand' => 'Cisco',
            'status' => 'Activo',
            'comments' => 'Switch principal',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/red/dispositivos/{$device->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $device->id)
            ->assertJsonPath('data.device_name', 'Switch Core')
            ->assertJsonPath('data.device_type', 'Switch')
            ->assertJsonPath('data.ip_address', '192.168.1.1')
            ->assertJsonPath('data.mac_address', 'AA:BB:CC:DD:EE:FF')
            ->assertJsonPath('data.location', 'Rack A')
            ->assertJsonPath('data.brand', 'Cisco')
            ->assertJsonPath('data.status', 'Activo')
            ->assertJsonPath('data.comments', 'Switch principal');
    }
}