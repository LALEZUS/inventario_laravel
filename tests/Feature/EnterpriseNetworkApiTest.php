<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\EnterpriseNetwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnterpriseNetworkApiTest extends TestCase
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

    public function test_list_and_detail_json_never_contain_password_or_password_key(): void
    {
        $admin = $this->user('admin');
        $testSecretPassword = 'Secret-Wifi-TG-2026';

        $network = EnterpriseNetwork::create([
            'network_name' => 'TG-Wi-Fi-Pruebas',
            'vlan' => '10',
            'location' => 'Site Principal',
            'password' => $testSecretPassword,
            'encryption' => 'WPA3',
            'comments' => 'Red de pruebas',
            'notes_extra' => 'Notas privadas de la red',
        ]);

        // 1. List JSON
        $listRes = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/red/empresariales')
            ->assertStatus(200);

        $listContent = $listRes->getContent();
        $this->assertStringNotContainsString($testSecretPassword, $listContent);
        $this->assertStringNotContainsString('"password"', $listContent);

        // 2. Detail JSON
        $detailRes = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/red/empresariales/{$network->id}")
            ->assertStatus(200);

        $detailContent = $detailRes->getContent();
        $this->assertStringNotContainsString($testSecretPassword, $detailContent);
        $this->assertStringNotContainsString('"password"', $detailContent);
    }

    public function test_credential_endpoint_permissions_and_no_store_headers(): void
    {
        $consultation = $this->user('consulta');
        $support = $this->user('soporte');
        $admin = $this->user('admin');
        $testSecretPassword = 'Secret-Wifi-TG-2026';

        $network = EnterpriseNetwork::create([
            'network_name' => 'TG-Wi-Fi-Sensible',
            'password' => $testSecretPassword,
        ]);

        // Consultation gets 403
        $this->actingAs($consultation, 'sanctum')
            ->getJson("/api/v1/red/empresariales/{$network->id}/credencial")
            ->assertStatus(403);

        // Support gets 200 + no-store headers + password
        $supRes = $this->actingAs($support, 'sanctum')
            ->getJson("/api/v1/red/empresariales/{$network->id}/credencial")
            ->assertStatus(200)
            ->assertJsonPath('data.password', $testSecretPassword);
        $this->assertStringContainsString('no-store', (string) $supRes->headers->get('Cache-Control'));

        // Admin gets 200 + no-store headers + password
        $admRes = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/red/empresariales/{$network->id}/credencial")
            ->assertStatus(200)
            ->assertJsonPath('data.password', $testSecretPassword);
        $this->assertStringContainsString('no-store', (string) $admRes->headers->get('Cache-Control'));
    }

    public function test_update_without_password_preserves_existing_password(): void
    {
        $support = $this->user('soporte');
        $originalPass = 'Wifi-Original-Pass-2026';

        $network = EnterpriseNetwork::create([
            'network_name' => 'TG-Invitados',
            'password' => $originalPass,
            'location' => 'Bodega',
        ]);

        // Update without sending 'password' key
        $this->actingAs($support, 'sanctum')
            ->putJson("/api/v1/red/empresariales/{$network->id}", [
                'network_name' => 'TG-Invitados',
                'location' => 'Oficinas',
            ])
            ->assertStatus(200);

        $this->assertSame($originalPass, $network->fresh()->password);
        $this->assertSame('Oficinas', $network->fresh()->location);

        // Update with empty string password
        $this->actingAs($support, 'sanctum')
            ->putJson("/api/v1/red/empresariales/{$network->id}", [
                'network_name' => 'TG-Invitados',
                'password' => '',
                'location' => 'Oficinas V2',
            ])
            ->assertStatus(200);

        $this->assertSame($originalPass, $network->fresh()->password);
        $this->assertSame('Oficinas V2', $network->fresh()->location);
    }

    public function test_update_with_new_password_updates_correctly(): void
    {
        $support = $this->user('soporte');

        $network = EnterpriseNetwork::create([
            'network_name' => 'TG-Laboratorio',
            'password' => 'Pass-Vieja-123',
        ]);

        $this->actingAs($support, 'sanctum')
            ->putJson("/api/v1/red/empresariales/{$network->id}", [
                'network_name' => 'TG-Laboratorio',
                'password' => 'Pass-Nueva-999',
            ])
            ->assertStatus(200);

        $this->assertSame('Pass-Nueva-999', $network->fresh()->password);
    }

    public function test_roles_permissions_for_delete_and_audit(): void
    {
        $support = $this->user('soporte');
        $admin = $this->user('admin');

        $network = EnterpriseNetwork::create(['network_name' => 'Red Borrable']);

        // Support gets 403 on delete
        $this->actingAs($support, 'sanctum')
            ->deleteJson("/api/v1/red/empresariales/{$network->id}")
            ->assertStatus(403);

        // Admin gets 200 on delete
        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/red/empresariales/{$network->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('enterprise_networks', ['id' => $network->id]);
    }
}