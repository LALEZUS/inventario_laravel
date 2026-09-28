<?php

namespace Tests\Feature;

use App\Models\AssetFile;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\HardwareAsset;
use App\Models\MaintenanceLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HardwareAssetDetailApiTest extends TestCase
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

    public function test_mobile_responsiva_supports_preview_and_web_options(): void
    {
        Storage::fake('local');
        $support = $this->user('soporte');
        $computer = HardwareAsset::create([
            'name' => 'Laptop Responsiva Móvil',
            'assigned_user' => 'Persona Responsable',
            'status' => 'ENTREGADO',
        ]);

        $filesBefore = AssetFile::count();
        $this->actingAs($support, 'sanctum')
            ->post("/api/v1/computadoras/{$computer->id}/responsiva/vista-previa", [
                'letterhead' => '0',
                'photo_layout' => 'large',
            ], ['Accept' => 'application/pdf'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="Vista_previa_responsiva.pdf"');
        $this->assertSame($filesBefore, AssetFile::count());

        $this->post("/api/v1/computadoras/{$computer->id}/responsiva", [
            'letterhead' => '0',
            'photo_layout' => 'large',
        ], ['Accept' => 'application/pdf'])
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $file = AssetFile::firstOrFail();
        $this->assertSame('Responsiva firmable - sin membrete', $file->label);
        $this->assertStringContainsString('Sin_membrete', $file->original_name);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'generate_responsiva',
            'entity_id' => (string) $computer->id,
        ]);
    }

    public function test_normal_resource_never_contains_sensitive_credentials(): void
    {
        $admin = $this->user('admin');
        $secretPass = 'AdminSecretPass2026';
        $anydeskSecret = '987654321';
        $rustdeskSecret = '123456789';

        $computer = HardwareAsset::create([
            'name' => 'PC Sensible Test',
            'code' => 'COMP-SECURE',
            'status' => 'DISPONIBLE',
            'admin_password' => $secretPass,
            'anydesk_id' => $anydeskSecret,
            'rustdesk_id' => $rustdeskSecret,
        ]);

        $res = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/computadoras/{$computer->id}")
            ->assertOk();

        $content = $res->getContent();
        $this->assertStringNotContainsString($secretPass, $content);
        $this->assertStringNotContainsString($anydeskSecret, $content);
        $this->assertStringNotContainsString($rustdeskSecret, $content);
        $this->assertStringNotContainsString('"admin_password"', $content);
        $this->assertStringNotContainsString('"anydesk_id"', $content);
        $this->assertStringNotContainsString('"rustdesk_id"', $content);
    }

    public function test_credentials_endpoint_permissions_and_no_store_headers(): void
    {
        $consultation = $this->user('consulta');
        $support = $this->user('soporte');
        $admin = $this->user('admin');

        $computer = HardwareAsset::create([
            'name' => 'PC Credenciales Test',
            'admin_password' => 'Pass123',
            'anydesk_id' => 'AD123',
            'rustdesk_id' => 'RD123',
        ]);

        // Consultation gets 403
        $this->actingAs($consultation, 'sanctum')
            ->getJson("/api/v1/computadoras/{$computer->id}/credenciales")
            ->assertStatus(403);

        // Support gets 200 + no-store headers
        $supRes = $this->actingAs($support, 'sanctum')
            ->getJson("/api/v1/computadoras/{$computer->id}/credenciales")
            ->assertOk()
            ->assertJsonPath('data.admin_password', 'Pass123')
            ->assertJsonPath('data.anydesk_id', 'AD123')
            ->assertJsonPath('data.rustdesk_id', 'RD123');
        $this->assertStringContainsString('no-store', (string) $supRes->headers->get('Cache-Control'));

        // Admin gets 200 + no-store headers
        $admRes = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/computadoras/{$computer->id}/credenciales")
            ->assertOk()
            ->assertJsonPath('data.admin_password', 'Pass123')
            ->assertJsonPath('data.anydesk_id', 'AD123')
            ->assertJsonPath('data.rustdesk_id', 'RD123');
        $this->assertStringContainsString('no-store', (string) $admRes->headers->get('Cache-Control'));
    }

    public function test_update_without_admin_password_preserves_existing_password(): void
    {
        $support = $this->user('soporte');
        $originalPass = 'OriginalPass2026';

        $computer = HardwareAsset::create([
            'name' => 'PC Preservar Pass',
            'status' => 'DISPONIBLE',
            'admin_password' => $originalPass,
        ]);

        // Update without sending admin_password key
        $this->actingAs($support, 'sanctum')
            ->putJson("/api/v1/computadoras/{$computer->id}", [
                'name' => 'PC Preservar Pass Editada',
                'status' => 'DISPONIBLE',
                'processor' => 'Intel i7',
            ])
            ->assertOk();

        $this->assertSame($originalPass, $computer->fresh()->admin_password);
        $this->assertSame('Intel i7', $computer->fresh()->processor);

        // Update with empty admin_password
        $this->actingAs($support, 'sanctum')
            ->putJson("/api/v1/computadoras/{$computer->id}", [
                'name' => 'PC Preservar Pass Editada V2',
                'status' => 'DISPONIBLE',
                'admin_password' => '',
            ])
            ->assertOk();

        $this->assertSame($originalPass, $computer->fresh()->admin_password);
    }

    public function test_update_with_new_admin_password_changes_password(): void
    {
        $support = $this->user('soporte');

        $computer = HardwareAsset::create([
            'name' => 'PC Cambio Pass',
            'status' => 'DISPONIBLE',
            'admin_password' => 'PassVieja',
        ]);

        $this->actingAs($support, 'sanctum')
            ->putJson("/api/v1/computadoras/{$computer->id}", [
                'name' => 'PC Cambio Pass',
                'status' => 'DISPONIBLE',
                'admin_password' => 'PassNueva999',
            ])
            ->assertOk();

        $this->assertSame('PassNueva999', $computer->fresh()->admin_password);
    }

    public function test_create_update_and_delete_computer_permissions(): void
    {
        $support = $this->user('soporte');
        $admin = $this->user('admin');

        // Support can create
        $res = $this->actingAs($support, 'sanctum')
            ->postJson('/api/v1/computadoras', [
                'name' => 'Nueva PC Soporte',
                'status' => 'DISPONIBLE',
                'ram' => '16GB',
                'value' => 15000.50,
            ])
            ->assertStatus(201);

        $id = $res->json('data.id');

        // Support cannot delete
        $this->actingAs($support, 'sanctum')
            ->deleteJson("/api/v1/computadoras/{$id}")
            ->assertStatus(403);

        // Admin can delete
        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/computadoras/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('hardware_assets', ['id' => $id]);
    }

    public function test_detail_auxiliary_endpoints_return_correct_data_and_urls(): void
    {
        Storage::fake('local');
        $user = $this->user('consulta');

        $employee = Employee::create([
            'full_name' => 'Empleado Test',
            'department' => 'Sistemas',
            'position' => 'Analista TI',
            'status' => 'Activo',
        ]);

        $computer = HardwareAsset::create([
            'name' => 'Equipo Pruebas Detalle',
            'code' => 'COMP-TEST',
            'status' => 'ENTREGADO',
            'employee_id' => $employee->id,
        ]);

        $assignment = Assignment::create([
            'asset_type' => 'inventory',
            'asset_id' => $computer->id,
            'employee_id' => $employee->id,
            'assigned_to' => 'Empleado Test',
            'department' => 'Sistemas',
            'date_assigned' => '2026-08-01',
            'condition_on_assign' => 'Excelente',
        ]);

        $photo = AssetFile::create([
            'asset_type' => 'inventory',
            'asset_id' => $computer->id,
            'original_name' => 'foto_frontal.jpg',
            'file_path' => 'laravel-local:assets/inventory/'.$computer->id.'/foto_frontal.jpg',
            'file_type' => 'application/octet-stream',
            'file_size' => 120000,
            'label' => 'Vista frontal',
        ]);
        Storage::disk('local')->put('assets/inventory/'.$computer->id.'/foto_frontal.jpg', 'fake-image');

        $file = AssetFile::create([
            'asset_type' => 'inventory',
            'asset_id' => $computer->id,
            'original_name' => 'especificaciones.pdf',
            'file_path' => 'laravel-local:assets/inventory/'.$computer->id.'/especificaciones.pdf',
            'file_type' => 'application/octet-stream',
            'file_size' => 450000,
            'label' => 'Manual de usuario',
        ]);
        Storage::disk('local')->put('assets/inventory/'.$computer->id.'/especificaciones.pdf', '%PDF-1.4 fake');

        $maint = MaintenanceLog::create([
            'asset_type' => 'inventory',
            'asset_id' => $computer->id,
            'category' => 'Preventivo',
            'description' => 'Limpieza de ventiladores',
            'date' => '2026-07-15',
            'technician' => 'Tecnico Local',
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'user_name' => 'Tester Detalle',
            'role' => 'consulta',
            'action' => 'assign',
            'entity' => 'assignments',
            'entity_id' => (string) $assignment->id,
        ]);

        // 1. Test Fotos
        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/computadoras/{$computer->id}/fotos")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $photo->id)
            ->assertJsonPath('data.0.label', 'Vista frontal')
            ->assertJsonPath('data.0.file_type', 'application/octet-stream');

        // 2. Test Archivos
        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/computadoras/{$computer->id}/archivos")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $file->id);

        $this->get("/api/v1/archivos/{$photo->id}/vista")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        $this->get("/api/v1/archivos/{$file->id}/descargar")
            ->assertOk()
            ->assertDownload('especificaciones.pdf');

        // 3. Test Asignaciones
        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/computadoras/{$computer->id}/asignaciones")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $assignment->id)
            ->assertJsonPath('data.0.assigned_to', 'Empleado Test');

        // 4. Test Mantenimientos
        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/computadoras/{$computer->id}/mantenimientos")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $maint->id)
            ->assertJsonPath('data.0.description', 'Limpieza de ventiladores');

        // 5. Test Bitacora (Admin required for audit endpoint)
        $admin = $this->user('admin');
        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/computadoras/{$computer->id}/bitacora")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.action', 'assign');
    }
}
