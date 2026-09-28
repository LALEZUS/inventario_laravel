<?php

namespace Tests\Feature\Api;

use App\Models\AssetFile;
use App\Models\AuditLog;
use App\Models\Cellphone;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CellphoneApiTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'username' => 'usr_'.$role.'_'.User::count(),
            'password' => Hash::make('password'),
            'full_name' => 'Usuario '.$role,
            'role' => $role,
        ]);
    }

    public function test_cellphone_resource_excludes_all_secret_passwords_and_pins(): void
    {
        $user = $this->user('soporte');

        $cellphone = Cellphone::create([
            'model' => 'Samsung S22',
            'phone_number' => '5551234567',
            'password' => 'SECRET_EMAIL_PASS_999',
            'updated_password' => 'SECRET_UPDATED_PASS_888',
            'app_lock_password' => '1234',
            'app_lock_answer' => 'RESPUESTA_SECRETA',
            'app_lock_pattern' => 'Z-PATTERN',
            'status' => 'En Uso',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/celulares/{$cellphone->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.model', 'Samsung S22');

        // Verify secrets are excluded
        $this->assertArrayNotHasKey('password', $response->json('data'));
        $this->assertArrayNotHasKey('updated_password', $response->json('data'));
        $this->assertArrayNotHasKey('app_lock_password', $response->json('data'));
        $this->assertArrayNotHasKey('app_lock_answer', $response->json('data'));
        $this->assertArrayNotHasKey('app_lock_pattern', $response->json('data'));
        $this->assertStringNotContainsString('SECRET_EMAIL_PASS_999', $response->getContent());
        $this->assertStringNotContainsString('RESPUESTA_SECRETA', $response->getContent());
    }

    public function test_credentials_endpoint_returns_secrets_for_authorized_roles_with_no_store_headers(): void
    {
        $admin = $this->user('admin');
        $support = $this->user('soporte');
        $consulta = $this->user('consulta');

        $cellphone = Cellphone::create([
            'model' => 'iPhone 14 Pro',
            'phone_number' => '5551112222',
            'password' => 'PASS_EMAIL_123',
            'updated_password' => 'UPD_PASS_456',
            'app_lock_password' => '9999',
            'app_lock_answer' => 'PERRO',
            'app_lock_pattern' => 'L-SHAPE',
            'has_app_lock' => true,
            'status' => 'En Uso',
        ]);

        // Consulta user gets 403 Forbidden
        $this->actingAs($consulta, 'sanctum')
            ->getJson("/api/v1/celulares/{$cellphone->id}/credenciales")
            ->assertStatus(403);

        // Support gets 200 + no-store headers
        $supRes = $this->actingAs($support, 'sanctum')
            ->getJson("/api/v1/celulares/{$cellphone->id}/credenciales")
            ->assertOk()
            ->assertJsonPath('data.password', 'PASS_EMAIL_123')
            ->assertJsonPath('data.updated_password', 'UPD_PASS_456')
            ->assertJsonPath('data.app_lock_password', '9999')
            ->assertJsonPath('data.app_lock_answer', 'PERRO')
            ->assertJsonPath('data.app_lock_pattern', 'L-SHAPE');
        $this->assertStringContainsString('no-store', (string) $supRes->headers->get('Cache-Control'));

        // Admin gets 200 + no-store headers
        $admRes = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/celulares/{$cellphone->id}/credenciales")
            ->assertOk()
            ->assertJsonPath('data.password', 'PASS_EMAIL_123')
            ->assertJsonPath('data.updated_password', 'UPD_PASS_456')
            ->assertJsonPath('data.app_lock_password', '9999')
            ->assertJsonPath('data.app_lock_answer', 'PERRO')
            ->assertJsonPath('data.app_lock_pattern', 'L-SHAPE');
        $this->assertStringContainsString('no-store', (string) $admRes->headers->get('Cache-Control'));
    }

    public function test_support_user_can_create_and_update_cellphone(): void
    {
        $support = $this->user('soporte');
        $employee = Employee::create(['full_name' => 'Juan Perez', 'department' => 'Ventas', 'status' => 'Activo']);

        $storeResponse = $this->actingAs($support, 'sanctum')
            ->postJson('/api/v1/celulares', [
                'employee_id' => $employee->id,
                'model' => 'iPhone 13',
                'phone_number' => '5559876543',
                'password' => 'INITIAL_PASS',
                'status' => 'En Uso',
            ]);

        $storeResponse->assertStatus(201)
            ->assertJsonPath('data.model', 'iPhone 13')
            ->assertJsonPath('data.employee_id', $employee->id)
            ->assertJsonPath('data.employee_name', 'Juan Perez');

        $id = $storeResponse->json('data.id');

        // Update without password preserves existing password
        $this->actingAs($support, 'sanctum')
            ->putJson("/api/v1/celulares/{$id}", [
                'model' => 'iPhone 13 Pro',
                'status' => 'Disponible',
            ])->assertStatus(200)
            ->assertJsonPath('data.model', 'iPhone 13 Pro');

        $cellphone = Cellphone::find($id);
        $this->assertEquals('INITIAL_PASS', $cellphone->password);
    }

    public function test_updating_cellphone_with_new_password_modifies_it(): void
    {
        $support = $this->user('soporte');

        $cellphone = Cellphone::create([
            'model' => 'Motorola Edge 40',
            'password' => 'OLD_PASS',
            'status' => 'Disponible',
        ]);

        $this->actingAs($support, 'sanctum')
            ->putJson("/api/v1/celulares/{$cellphone->id}", [
                'model' => 'Motorola Edge 40 Ultra',
                'password' => 'NEW_PASS_2026',
                'status' => 'En Uso',
            ])->assertStatus(200);

        $cellphone->refresh();
        $this->assertEquals('NEW_PASS_2026', $cellphone->password);
    }

    public function test_photos_and_files_upload_and_listing(): void
    {
        Storage::fake('local');

        $support = $this->user('soporte');

        $cellphone = Cellphone::create([
            'model' => 'Google Pixel 7',
            'status' => 'Disponible',
        ]);

        // Upload Photo
        $photo = UploadedFile::fake()->image('celular_frontal.jpg', 800, 600);
        $uploadPhotoResponse = $this->actingAs($support, 'sanctum')
            ->postJson("/api/v1/celulares/{$cellphone->id}/fotos", [
                'photo' => $photo,
                'label' => 'Vista frontal',
            ]);

        $uploadPhotoResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.label', 'Vista frontal');

        // List Photos
        $this->actingAs($support, 'sanctum')
            ->getJson("/api/v1/celulares/{$cellphone->id}/fotos")
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // Upload File
        $document = UploadedFile::fake()->create('contrato.pdf', 500, 'application/pdf');
        $uploadFileResponse = $this->actingAs($support, 'sanctum')
            ->postJson("/api/v1/celulares/{$cellphone->id}/archivos", [
                'file' => $document,
                'label' => 'Contrato firmado',
            ]);

        $uploadFileResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.label', 'Contrato firmado');

        // List Files
        $this->actingAs($support, 'sanctum')
            ->getJson("/api/v1/celulares/{$cellphone->id}/archivos")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_audit_logs_permissions_and_secrets_protection(): void
    {
        $admin = $this->user('admin');
        $consulta = $this->user('consulta');

        $cellphone = Cellphone::create([
            'model' => 'Samsung Galaxy A54',
            'password' => 'TOP_SECRET_PASS',
            'status' => 'Disponible',
        ]);

        // Audit log query by admin
        $auditResponse = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/celulares/{$cellphone->id}/bitacora");

        $auditResponse->assertStatus(200);
        $this->assertStringNotContainsString('TOP_SECRET_PASS', $auditResponse->getContent());

        // Audit log query by consulta (Forbidden)
        $this->actingAs($consulta, 'sanctum')
            ->getJson("/api/v1/celulares/{$cellphone->id}/bitacora")
            ->assertStatus(403);
    }

    public function test_delete_permissions_admin_allowed_others_forbidden(): void
    {
        $admin = $this->user('admin');
        $soporte = $this->user('soporte');

        $cellphone = Cellphone::create([
            'model' => 'Honor Magic 5',
            'status' => 'Baja',
        ]);

        // Soporte cannot delete
        $this->actingAs($soporte, 'sanctum')
            ->deleteJson("/api/v1/celulares/{$cellphone->id}")
            ->assertStatus(403);

        // Admin can delete
        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/celulares/{$cellphone->id}")
            ->assertStatus(200);

        $this->assertNull(Cellphone::find($cellphone->id));
    }
}