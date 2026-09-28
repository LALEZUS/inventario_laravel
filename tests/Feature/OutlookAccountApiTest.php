<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\OutlookAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OutlookAccountApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $soporte;
    private User $consulta;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'username' => 'admin_user',
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->soporte = User::create([
            'username' => 'soporte_user',
            'name' => 'Soporte User',
            'email' => 'soporte@test.com',
            'password' => Hash::make('password'),
            'role' => 'soporte',
        ]);

        $this->consulta = User::create([
            'username' => 'consulta_user',
            'name' => 'Consulta User',
            'email' => 'consulta@test.com',
            'password' => Hash::make('password'),
            'role' => 'consulta',
        ]);

        $this->employee = Employee::create([
            'full_name' => 'María González',
            'department' => 'Sistemas',
            'status' => 'Activo',
        ]);
    }

    public function test_index_and_detail_do_not_expose_password_or_sensitive_column_name(): void
    {
        $account = OutlookAccount::create([
            'correo' => 'maria.gonzalez@totalground.com',
            'contraseña' => 'Secret123!',
            'estatus' => 'ACTIVA',
            'employee_id' => $this->employee->id,
            'servidor_entrada' => 'mail.totalground.com',
            'puerto_entrada' => '995',
            'ssl_entrada' => true,
            'servidor_salida' => 'mail.totalground.com',
            'puerto_salida' => '465',
            'cifrado_salida' => 'SSL/TLS',
        ]);

        $resIndex = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/correos-outlook');

        $resIndex->assertStatus(200)
            ->assertJsonPath('data.0.correo', 'maria.gonzalez@totalground.com')
            ->assertJsonMissingPath('data.0.password')
            ->assertJsonMissingPath('data.0.contraseña')
            ->assertJsonMissingPath('data.0.contraseÃ±a')
            ->assertJsonMissingPath('data.0.audit_logs');

        $resDetail = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/correos-outlook/' . $account->id);

        $resDetail->assertStatus(200)
            ->assertJsonPath('data.correo', 'maria.gonzalez@totalground.com')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.contraseña')
            ->assertJsonMissingPath('data.contraseÃ±a')
            ->assertJsonPath('data.employee_name', 'María González');
    }

    public function test_secret_endpoint_returns_normalized_password_with_security_header(): void
    {
        $account = OutlookAccount::create([
            'correo' => 'tech@totalground.com',
            'contraseña' => 'SuperSecretPass99',
            'estatus' => 'ACTIVA',
        ]);

        // Admin gets normalized password and security header
        $resAdmin = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/correos-outlook/{$account->id}/secreto");

        $resAdmin->assertStatus(200)
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJson([
                'success' => true,
                'data' => [
                    'password' => 'SuperSecretPass99',
                ],
            ]);

        // Soporte gets normalized password
        $resSoporte = $this->actingAs($this->soporte, 'sanctum')
            ->getJson("/api/v1/correos-outlook/{$account->id}/secreto");

        $resSoporte->assertStatus(200)
            ->assertJsonPath('data.password', 'SuperSecretPass99');

        // Consulta gets 403 Forbidden
        $resConsulta = $this->actingAs($this->consulta, 'sanctum')
            ->getJson("/api/v1/correos-outlook/{$account->id}/secreto");

        $resConsulta->assertStatus(403);
    }

    public function test_create_requires_password_and_validates_422(): void
    {
        $response = $this->actingAs($this->soporte, 'sanctum')
            ->postJson('/api/v1/correos-outlook', [
                'correo' => 'nuevo@totalground.com',
                'estatus' => 'ACTIVA',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_create_success_by_soporte(): void
    {
        $response = $this->actingAs($this->soporte, 'sanctum')
            ->postJson('/api/v1/correos-outlook', [
                'correo' => 'ventas@totalground.com',
                'password' => 'PassVentas2026',
                'estatus' => 'ACTIVA',
                'employee_id' => $this->employee->id,
                'comentarios' => 'Cuenta de correo de ventas',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.correo', 'ventas@totalground.com')
            ->assertJsonPath('data.employee_name', 'María González')
            ->assertJsonMissingPath('data.password');
    }

    public function test_update_without_password_preserves_existing_password(): void
    {
        $account = OutlookAccount::create([
            'correo' => 'mantenimiento@totalground.com',
            'contraseña' => 'OriginalPassword123',
            'estatus' => 'ACTIVA',
        ]);

        $response = $this->actingAs($this->soporte, 'sanctum')
            ->putJson('/api/v1/correos-outlook/' . $account->id, [
                'correo' => 'mantenimiento@totalground.com',
                'estatus' => 'BAJA',
                'comentarios' => 'Desactivado temporalmente',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.estatus', 'BAJA');

        // Verify password in DB was preserved
        $account->refresh();
        $secretVal = $account->getAttribute('contraseña') ?? $account->getAttribute('contraseÃ±a');
        $this->assertEquals('OriginalPassword123', $secretVal);
    }

    public function test_update_with_new_password_changes_password(): void
    {
        $account = OutlookAccount::create([
            'correo' => 'desarrollo@totalground.com',
            'contraseña' => 'OldPassword123',
            'estatus' => 'ACTIVA',
        ]);

        $response = $this->actingAs($this->soporte, 'sanctum')
            ->putJson('/api/v1/correos-outlook/' . $account->id, [
                'correo' => 'desarrollo@totalground.com',
                'password' => 'NewSecurePassword456',
                'estatus' => 'ACTIVA',
            ]);

        $response->assertStatus(200);

        $account->refresh();
        $secretVal = $account->getAttribute('contraseña') ?? $account->getAttribute('contraseÃ±a');
        $this->assertEquals('NewSecurePassword456', $secretVal);
    }

    public function test_search_by_correo_servidor_and_filter_by_estatus(): void
    {
        OutlookAccount::create([
            'correo' => 'alerta.server1@totalground.com',
            'contraseña' => 'Pass123!',
            'estatus' => 'ACTIVA',
            'servidor_entrada' => 'mail.server1.com',
        ]);
        OutlookAccount::create([
            'correo' => 'alerta.server2@totalground.com',
            'contraseña' => 'Pass123!',
            'estatus' => 'BAJA',
            'servidor_entrada' => 'mail.server2.com',
        ]);

        // Search by correo
        $resSearchCorreo = $this->actingAs($this->consulta, 'sanctum')
            ->getJson('/api/v1/correos-outlook?search=server1');
        $resSearchCorreo->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.correo', 'alerta.server1@totalground.com');

        // Filter by estatus
        $resFilterStatus = $this->actingAs($this->consulta, 'sanctum')
            ->getJson('/api/v1/correos-outlook?estatus=BAJA');
        $resFilterStatus->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.correo', 'alerta.server2@totalground.com');
    }

    public function test_role_permissions_for_write_and_delete_operations(): void
    {
        $account = OutlookAccount::create([
            'correo' => 'prueba.permisos@totalground.com',
            'contraseña' => 'Pass123!',
            'estatus' => 'ACTIVA',
        ]);

        // Consulta role: POST, PUT, DELETE 403
        $this->actingAs($this->consulta, 'sanctum')
            ->postJson('/api/v1/correos-outlook', ['correo' => 'x@tg.com', 'password' => '123', 'estatus' => 'ACTIVA'])
            ->assertStatus(403);

        $this->actingAs($this->consulta, 'sanctum')
            ->putJson('/api/v1/correos-outlook/' . $account->id, ['correo' => 'x@tg.com', 'estatus' => 'ACTIVA'])
            ->assertStatus(403);

        $this->actingAs($this->consulta, 'sanctum')
            ->deleteJson('/api/v1/correos-outlook/' . $account->id)
            ->assertStatus(403);

        // Soporte role: DELETE 403, POST/PUT permitted
        $this->actingAs($this->soporte, 'sanctum')
            ->deleteJson('/api/v1/correos-outlook/' . $account->id)
            ->assertStatus(403);

        // Admin role: DELETE permitted
        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson('/api/v1/correos-outlook/' . $account->id)
            ->assertStatus(200);

        $this->assertDatabaseMissing('correos_outlook', ['id' => $account->id]);
    }

    public function test_audit_logs_contain_no_plain_passwords(): void
    {
        $account = OutlookAccount::create([
            'correo' => 'audit.test@totalground.com',
            'contraseña' => 'TopSecretPassWord',
            'estatus' => 'ACTIVA',
        ]);

        $this->actingAs($this->soporte, 'sanctum')
            ->putJson('/api/v1/correos-outlook/' . $account->id, [
                'correo' => 'audit.test@totalground.com',
                'password' => 'ChangedPassWord99',
                'estatus' => 'ACTIVA',
            ]);

        $resDetail = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/correos-outlook/' . $account->id);

        $resDetail->assertStatus(200);

        $jsonStr = json_encode($resDetail->json());
        $this->assertStringNotContainsString('TopSecretPassWord', $jsonStr);
        $this->assertStringNotContainsString('ChangedPassWord99', $jsonStr);
    }
}
