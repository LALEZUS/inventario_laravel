<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\WatchguardUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WatchguardUserApiTest extends TestCase
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
        $testSecretPassword = 'Secret-WG-9999-SuperPrivate';

        $wgUser = WatchguardUser::create([
            'username' => 'vpn-test-user',
            'password' => $testSecretPassword,
            'assigned_to' => 'Empleado VPN',
            'area' => 'Sistemas',
            'ip' => '192.168.15.101',
            'comments' => 'Usuario especial VPN',
        ]);

        // 1. List JSON test
        $listRes = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/red/watchguard')
            ->assertStatus(200);

        $listContent = $listRes->getContent();
        $this->assertStringNotContainsString($testSecretPassword, $listContent);
        $this->assertStringNotContainsString('"password"', $listContent);

        // 2. Detail JSON test
        $detailRes = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/red/watchguard/{$wgUser->id}")
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
        $testSecretPassword = 'Secret-WG-9999-SuperPrivate';

        $wgUser = WatchguardUser::create([
            'username' => 'vpn-test-cred',
            'password' => $testSecretPassword,
        ]);

        // Consultation gets 403
        $this->actingAs($consultation, 'sanctum')
            ->getJson("/api/v1/red/watchguard/{$wgUser->id}/credencial")
            ->assertStatus(403);

        // Support gets 200 + no-store headers + password
        $supRes = $this->actingAs($support, 'sanctum')
            ->getJson("/api/v1/red/watchguard/{$wgUser->id}/credencial")
            ->assertStatus(200)
            ->assertJsonPath('data.password', $testSecretPassword);
        $this->assertStringContainsString('no-store', (string) $supRes->headers->get('Cache-Control'));

        // Admin gets 200 + no-store headers + password
        $admRes = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/red/watchguard/{$wgUser->id}/credencial")
            ->assertStatus(200)
            ->assertJsonPath('data.password', $testSecretPassword);
        $this->assertStringContainsString('no-store', (string) $admRes->headers->get('Cache-Control'));
    }

    public function test_create_without_password_returns_422_validation_error(): void
    {
        $support = $this->user('soporte');

        $this->actingAs($support, 'sanctum')
            ->postJson('/api/v1/red/watchguard', [
                'username' => 'vpn-nuevo',
                'assigned_to' => 'Empleado Nuevo',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_invalid_ip_returns_422(): void
    {
        $support = $this->user('soporte');

        $this->actingAs($support, 'sanctum')
            ->postJson('/api/v1/red/watchguard', [
                'username' => 'vpn-ip-invalida',
                'password' => 'Pass-1234',
                'ip' => '999.888.777.666',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['ip']);
    }

    public function test_update_without_password_or_empty_password_preserves_existing(): void
    {
        $support = $this->user('soporte');
        $originalPass = 'Original-Secret-2026';

        $wgUser = WatchguardUser::create([
            'username' => 'vpn-persistente',
            'password' => $originalPass,
            'assigned_to' => 'Usuario Original',
        ]);

        // Update without sending 'password' key
        $this->actingAs($support, 'sanctum')
            ->putJson("/api/v1/red/watchguard/{$wgUser->id}", [
                'username' => 'vpn-persistente',
                'assigned_to' => 'Usuario Nombre Editado',
            ])
            ->assertStatus(200);

        $this->assertSame($originalPass, $wgUser->fresh()->password);
        $this->assertSame('Usuario Nombre Editado', $wgUser->fresh()->assigned_to);

        // Update with empty string password
        $this->actingAs($support, 'sanctum')
            ->putJson("/api/v1/red/watchguard/{$wgUser->id}", [
                'username' => 'vpn-persistente',
                'password' => '',
                'assigned_to' => 'Usuario Reeditado',
            ])
            ->assertStatus(200);

        $this->assertSame($originalPass, $wgUser->fresh()->password);
        $this->assertSame('Usuario Reeditado', $wgUser->fresh()->assigned_to);
    }

    public function test_update_with_new_password_updates_correctly(): void
    {
        $support = $this->user('soporte');

        $wgUser = WatchguardUser::create([
            'username' => 'vpn-cambio-pass',
            'password' => 'Pass-Vieja-123',
        ]);

        $this->actingAs($support, 'sanctum')
            ->putJson("/api/v1/red/watchguard/{$wgUser->id}", [
                'username' => 'vpn-cambio-pass',
                'password' => 'Pass-Nueva-456',
            ])
            ->assertStatus(200);

        $this->assertSame('Pass-Nueva-456', $wgUser->fresh()->password);
    }

    public function test_roles_permissions_for_create_update_delete_and_audit(): void
    {
        $consultation = $this->user('consulta');
        $support = $this->user('soporte');
        $admin = $this->user('admin');

        $wgUser = WatchguardUser::create([
            'username' => 'vpn-permisos',
            'password' => 'Secret-123',
        ]);

        // Consultation cannot create or update
        $this->actingAs($consultation, 'sanctum')
            ->postJson('/api/v1/red/watchguard', ['username' => 'u', 'password' => 'p'])
            ->assertStatus(403);

        $this->actingAs($consultation, 'sanctum')
            ->putJson("/api/v1/red/watchguard/{$wgUser->id}", ['username' => 'u2'])
            ->assertStatus(403);

        // Support cannot delete
        $this->actingAs($support, 'sanctum')
            ->deleteJson("/api/v1/red/watchguard/{$wgUser->id}")
            ->assertStatus(403);

        // Support/Consultation cannot view audit
        $this->actingAs($consultation, 'sanctum')
            ->getJson("/api/v1/red/watchguard/{$wgUser->id}/bitacora")
            ->assertStatus(403);

        $this->actingAs($support, 'sanctum')
            ->getJson("/api/v1/red/watchguard/{$wgUser->id}/bitacora")
            ->assertStatus(403);

        // Admin can view audit and delete
        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/red/watchguard/{$wgUser->id}/bitacora")
            ->assertStatus(200);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/red/watchguard/{$wgUser->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('watchguard_users', ['id' => $wgUser->id]);
    }

    public function test_audit_logs_never_contain_literal_password(): void
    {
        $admin = $this->user('admin');
        $testSecretPassword = 'Secret-WG-Audit-Protection-123';

        $wgUser = WatchguardUser::create([
            'username' => 'vpn-audit-test',
            'password' => $testSecretPassword,
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'create',
            'entity' => 'watchguard_users',
            'entity_id' => (string) $wgUser->id,
            'before_data' => null,
            'after_data' => ['username' => 'vpn-audit-test', 'password' => '[PROTECTED]'],
        ]);

        $res = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/red/watchguard/{$wgUser->id}/bitacora")
            ->assertStatus(200);

        $resContent = $res->getContent();
        $this->assertStringNotContainsString($testSecretPassword, $resContent);
    }
}