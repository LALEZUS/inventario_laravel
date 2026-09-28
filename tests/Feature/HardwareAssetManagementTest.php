<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\AssetFile;
use App\Models\Employee;
use App\Models\HardwareAsset;
use App\Models\User;
use App\Services\GlobalSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HardwareAssetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_can_create_a_computer_from_an_nfo(): void
    {
        Storage::fake('local');
        $user = $this->user('soporte');
        $nfo = UploadedFile::fake()->createWithContent('equipo.nfo', $this->sampleNfo());

        $response = $this->actingAs($user)->post(route('computers.store'), [
            'status' => 'DISPONIBLE',
            'nfo_file' => $nfo,
        ]);

        $computer = HardwareAsset::firstOrFail();
        $response->assertRedirect(route('computers.show', $computer));
        $this->assertSame('ThinkCentre M70Q', $computer->name);
        $this->assertSame('LENOVO', $computer->brand);
        $this->assertSame('16.0 GB', $computer->ram);
        Storage::disk('local')->assertExists($computer->nfo_file);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'entity' => 'hardware_assets',
            'entity_id' => (string) $computer->id,
        ]);
    }

    public function test_consultation_role_cannot_create_or_edit_computers(): void
    {
        $user = $this->user('consulta');
        $computer = HardwareAsset::create(['name' => 'Equipo protegido', 'status' => 'DISPONIBLE']);

        $this->actingAs($user)->get(route('computers.create'))->assertForbidden();
        $this->actingAs($user)->put(route('computers.update', $computer), [
            'name' => 'Cambio no permitido',
            'status' => 'DISPONIBLE',
        ])->assertForbidden();
    }

    public function test_support_cannot_delete_a_computer(): void
    {
        $user = $this->user('soporte');
        $computer = HardwareAsset::create(['name' => 'Equipo protegido', 'status' => 'DISPONIBLE']);

        $this->actingAs($user)->delete(route('computers.destroy', $computer))->assertForbidden();
        $this->assertDatabaseHas('hardware_assets', ['id' => $computer->id]);
    }

    public function test_admin_can_update_and_delete_with_audit_history(): void
    {
        $user = $this->user('admin');
        $computer = HardwareAsset::create(['name' => 'Equipo anterior', 'status' => 'DISPONIBLE']);

        $this->actingAs($user)->put(route('computers.update', $computer), [
            'name' => 'Equipo actualizado',
            'status' => 'MANTENIMIENTO',
            'processor' => 'Intel Core i7',
        ])->assertRedirect(route('computers.show', $computer));

        $this->assertDatabaseHas('hardware_assets', [
            'id' => $computer->id,
            'name' => 'Equipo actualizado',
            'status' => 'MANTENIMIENTO',
        ]);
        $this->assertSame(1, AuditLog::where('action', 'update')->where('entity_id', $computer->id)->count());

        $this->actingAs($user)->delete(route('computers.destroy', $computer))
            ->assertRedirect(route('computers.index'));

        $this->assertDatabaseMissing('hardware_assets', ['id' => $computer->id]);
        $this->assertSame(1, AuditLog::where('action', 'delete')->where('entity_id', $computer->id)->count());
    }

    public function test_remote_access_ids_can_be_saved_displayed_and_searched(): void
    {
        $user = $this->user('soporte');

        $response = $this->actingAs($user)->post(route('computers.store'), [
            'name' => 'Equipo soporte remoto',
            'status' => 'DISPONIBLE',
            'anydesk_id' => '123 456 789',
            'rustdesk_id' => '987-654-321',
            'value' => '12500.50',
        ]);

        $computer = HardwareAsset::firstOrFail();
        $response->assertRedirect(route('computers.show', $computer));
        $this->assertSame('123 456 789', $computer->anydesk_id);
        $this->assertSame('987-654-321', $computer->rustdesk_id);
        $this->assertSame('12500.50', $computer->value);

        $this->actingAs($user)
            ->get(route('computers.show', $computer))
            ->assertOk()
            ->assertSee('ID de AnyDesk')
            ->assertSee('123 456 789')
            ->assertSee('ID de RustDesk')
            ->assertSee('987-654-321')
            ->assertSee('$12,500.50');
        $this->assertSame('$12,500.50', app(\App\Services\ResponsivaGenerator::class)->specifications($computer)['Valor del equipo']);

        $results = app(GlobalSearch::class)->search('987-654-321', 40, false);
        $this->assertTrue($results->contains(fn (array $result) => $result['url'] === route('computers.show', $computer)));
    }

    public function test_admin_can_reveal_computer_admin_password_but_consultation_cannot(): void
    {
        $computer = HardwareAsset::create([
            'name' => 'Equipo con acceso protegido',
            'status' => 'DISPONIBLE',
            'admin_password' => 'Admin-Secret-2026',
        ]);

        $this->actingAs($this->user('admin'))
            ->get(route('computers.show', $computer))
            ->assertOk()
            ->assertSee('data-secret-url="'.route('computers.secrets', $computer).'"', false)
            ->assertDontSee('Admin-Secret-2026')
            ->assertSee('Mostrar');

        $this->get(route('computers.secrets', $computer))
            ->assertOk()
            ->assertJsonPath('admin_password', 'Admin-Secret-2026')
            ->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');

        $this->actingAs($this->user('consulta'))
            ->get(route('computers.show', $computer))
            ->assertOk()
            ->assertDontSee('Admin-Secret-2026')
            ->assertDontSee('Contraseña de administrador');
    }

    public function test_legacy_jpg_attachments_are_displayed_as_inline_photos(): void
    {
        $user = $this->user('soporte');
        $computer = HardwareAsset::create(['name' => 'Equipo con fotos', 'status' => 'DISPONIBLE']);
        $photo = AssetFile::create([
            'asset_type' => 'inventory',
            'asset_id' => $computer->id,
            'original_name' => 'scaled_64745.jpg',
            'file_path' => 'evidencias/scaled_64745.jpg',
            'file_type' => 'application/octet-stream',
            'label' => 'Foto de evidencia',
        ]);

        $this->assertTrue($photo->isImage());
        $this->assertSame('image/jpeg', $photo->previewMimeType());

        $this->actingAs($user)
            ->get(route('computers.show', $computer))
            ->assertOk()
            ->assertSee('data-photo-preview', false)
            ->assertSee(route('asset-files.preview', $photo), false)
            ->assertSee('computer-photo-lightbox', false);
    }

    public function test_computer_form_lists_inactive_employees_with_distinct_label(): void
    {
        $user = $this->user('soporte');
        $activeEmployee = Employee::create([
            'full_name' => 'Empleado Activo Test',
            'status' => 'Activo',
            'department' => 'Sistemas',
        ]);
        $inactiveEmployee = Employee::create([
            'full_name' => 'Empleado Inactivo Test',
            'status' => 'Inactivo',
            'department' => 'Ventas',
        ]);

        $computer = HardwareAsset::create([
            'name' => 'PC Prueba',
            'status' => 'DISPONIBLE',
        ]);

        $response = $this->actingAs($user)->get(route('computers.edit', $computer));

        $response->assertOk();
        $response->assertSee('label="Empleados activos"', false);
        $response->assertSee('label="Empleados inactivos"', false);
        $response->assertSee('Empleado Activo Test - Sistemas');
        $response->assertSee('Empleado Inactivo Test - Ventas (Inactivo)');
    }

    private function user(string $role): User
    {
        return User::create([
            'username' => 'user_'.$role,
            'password' => Hash::make('password'),
            'full_name' => 'Usuario '.$role,
            'role' => $role,
        ]);
    }

    private function sampleNfo(): string
    {
        return <<<'XML'
<?xml version="1.0"?>
<MsInfo>
  <Category name="Resumen del sistema">
    <Data><Elemento>Nombre del SO</Elemento><Valor>Windows 11 Pro</Valor></Data>
    <Data><Elemento>Fabricante del sistema</Elemento><Valor>LENOVO</Valor></Data>
    <Data><Elemento>Modelo del sistema</Elemento><Valor>ThinkCentre M70Q</Valor></Data>
    <Data><Elemento>Procesador</Elemento><Valor>Intel Core i5-14500T, 14 nucleos</Valor></Data>
    <Data><Elemento>Memoria fisica instalada (RAM)</Elemento><Valor>16.0 GB</Valor></Data>
  </Category>
</MsInfo>
XML;
    }
}
