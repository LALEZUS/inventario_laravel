<?php

namespace Tests\Feature;

use App\Models\AccountCredential;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountCredentialApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $soporteUser;
    protected User $consultaUser;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'username' => 'admin_user',
            'full_name' => 'Admin User',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->soporteUser = User::create([
            'username' => 'soporte_user',
            'full_name' => 'Soporte User',
            'password' => Hash::make('password'),
            'role' => 'soporte',
        ]);

        $this->consultaUser = User::create([
            'username' => 'consulta_user',
            'full_name' => 'Consulta User',
            'password' => Hash::make('password'),
            'role' => 'consulta',
        ]);

        $this->employee = Employee::create([
            'full_name' => 'Juan Pérez',
            'department' => 'Sistemas',
            'status' => 'Activo',
        ]);
    }

    public function test_list_and_detail_never_contain_password(): void
    {
        $credential = AccountCredential::create([
            'email' => 'admin@totalground.com',
            'password' => 'SuperSecret123',
            'account_type' => 'Microsoft 365',
            'assigned_to' => 'Juan Pérez',
            'status' => 'Activo',
        ]);

        // Index
        $indexResp = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/credenciales-cuentas');
        $indexResp->assertStatus(200)
            ->assertJsonMissing(['password'])
            ->assertJsonMissing(['audit_logs']);

        // Show
        $showResp = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/credenciales-cuentas/{$credential->id}");
        $showResp->assertStatus(200)
            ->assertJsonMissing(['password']);
    }

    public function test_secret_endpoint_permissions_and_no_store_header(): void
    {
        $credential = AccountCredential::create([
            'email' => 'tech@totalground.com',
            'password' => 'SecretPassword456',
            'account_type' => 'Gmail',
            'status' => 'Activo',
        ]);

        // Admin -> 200 + no-store header
        $adminResp = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/credenciales-cuentas/{$credential->id}/secreto");
        $adminResp->assertStatus(200)
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('data.password', 'SecretPassword456');

        // Soporte -> 200 + no-store header
        $soporteResp = $this->actingAs($this->soporteUser, 'sanctum')
            ->getJson("/api/v1/credenciales-cuentas/{$credential->id}/secreto");
        $soporteResp->assertStatus(200)
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('data.password', 'SecretPassword456');

        // Consulta -> 403 Forbidden
        $consultaResp = $this->actingAs($this->consultaUser, 'sanctum')
            ->getJson("/api/v1/credenciales-cuentas/{$credential->id}/secreto");
        $consultaResp->assertStatus(403);
    }

    public function test_create_without_password_returns_422_validation_error(): void
    {
        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->postJson('/api/v1/credenciales-cuentas', [
                'email' => 'nueva@totalground.com',
                'account_type' => 'Microsoft 365',
                'status' => 'Activo',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_update_without_password_preserves_existing_password(): void
    {
        $credential = AccountCredential::create([
            'email' => 'hosting@totalground.com',
            'password' => 'OriginalPassword789',
            'account_type' => 'Hospedaje',
            'status' => 'Activo',
        ]);

        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->putJson("/api/v1/credenciales-cuentas/{$credential->id}", [
                'email' => 'hosting@totalground.com',
                'password' => null,
                'account_type' => 'Hospedaje',
                'status' => 'Activo',
                'comments' => 'Sin cambio de clave',
            ]);

        $response->assertStatus(200);

        $this->assertEquals('OriginalPassword789', $credential->fresh()->password);
    }

    public function test_update_with_new_password_changes_password(): void
    {
        $credential = AccountCredential::create([
            'email' => 'personal@totalground.com',
            'password' => 'OldPass123',
            'account_type' => 'Personal',
            'status' => 'Activo',
        ]);

        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->putJson("/api/v1/credenciales-cuentas/{$credential->id}", [
                'email' => 'personal@totalground.com',
                'password' => 'NewPass999',
                'account_type' => 'Personal',
                'status' => 'Activo',
            ]);

        $response->assertStatus(200);

        $this->assertEquals('NewPass999', $credential->fresh()->password);
    }

    public function test_soporte_cannot_delete_but_admin_can(): void
    {
        $credential = AccountCredential::create([
            'email' => 'baja@totalground.com',
            'password' => 'PassToDelete',
            'account_type' => 'Gmail',
            'status' => 'Baja',
        ]);

        // Soporte -> 403
        $soporteResp = $this->actingAs($this->soporteUser, 'sanctum')
            ->deleteJson("/api/v1/credenciales-cuentas/{$credential->id}");
        $soporteResp->assertStatus(403);

        // Admin -> 200
        $adminResp = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/credenciales-cuentas/{$credential->id}");
        $adminResp->assertStatus(200);

        $this->assertDatabaseMissing('account_management', ['id' => $credential->id]);
    }

    public function test_audit_logs_and_global_search_never_contain_real_secret(): void
    {
        $credential = AccountCredential::create([
            'email' => 'audit_test@totalground.com',
            'password' => 'TopSecretKeyWord',
            'account_type' => 'Microsoft 365',
            'status' => 'Activo',
        ]);

        // Detail audit logs check
        $detailResp = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/credenciales-cuentas/{$credential->id}");
        $detailResp->assertStatus(200)
            ->assertJsonMissing(['TopSecretKeyWord']);

        // Global search check
        $searchResp = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/buscar?q=audit_test');
        $searchResp->assertStatus(200)
            ->assertJsonMissing(['TopSecretKeyWord']);
    }

    public function test_employee_relationship_and_assigned_to_sync(): void
    {
        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->postJson('/api/v1/credenciales-cuentas', [
                'email' => 'juan.perez@totalground.com',
                'password' => 'PassJuan123',
                'account_type' => 'Microsoft 365',
                'employee_id' => $this->employee->id,
                'status' => 'Activo',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.employee_id', $this->employee->id)
            ->assertJsonPath('data.employee_name', 'Juan Pérez');

        $this->assertDatabaseHas('account_management', [
            'email' => 'juan.perez@totalground.com',
            'employee_id' => $this->employee->id,
            'assigned_to' => 'Juan Pérez',
        ]);
    }
}