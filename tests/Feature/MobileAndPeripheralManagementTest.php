<?php

namespace Tests\Feature;

use App\Models\AssetFile;
use App\Models\Assignment;
use App\Models\Cellphone;
use App\Models\Employee;
use App\Models\HardwareAsset;
use App\Models\Peripheral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileAndPeripheralManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_can_create_and_search_a_cellphone_linked_to_an_employee(): void
    {
        $employee = Employee::create(['full_name' => 'Ulises Ramirez', 'department' => 'Sistemas', 'status' => 'Activo']);
        $response = $this->actingAs($this->user('soporte'))->post(route('cellphones.store'), [
            'employee_id' => $employee->id,
            'model' => 'Pixel 9 Pro',
            'status' => 'En Uso',
            'phone_number' => '3312345678',
            'email_account' => 'ulises@example.com',
            'password' => 'secret-one',
            'has_app_lock' => '1',
            'app_lock_pattern' => '1-2-3-6',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $cellphone = Cellphone::firstOrFail();
        $response->assertRedirect(route('cellphones.show', $cellphone));
        $this->assertSame('Ulises Ramirez', $cellphone->employee_name_legacy);
        $this->assertSame('Sistemas', $cellphone->area);
        $this->actingAs($this->user('consulta', 'consulta-search'))->get(route('cellphones.index', ['search' => 'Pixel 9']))
            ->assertOk()->assertSee('Ulises Ramirez')->assertSee('Pixel 9 Pro');
        $this->assertDatabaseHas('audit_logs', ['entity' => 'cellphones', 'action' => 'create']);
    }

    public function test_cellphones_can_be_ordered_by_latest_created_or_updated_record(): void
    {
        Cellphone::create([
            'model' => 'Modelo creado primero',
            'employee_name_legacy' => 'Registro actualizado recientemente',
            'status' => 'En Uso',
            'created_at' => '2026-08-01 08:00:00',
            'updated_at' => '2026-08-20 08:00:00',
        ]);
        Cellphone::create([
            'model' => 'Modelo creado después',
            'employee_name_legacy' => 'Registro agregado recientemente',
            'status' => 'En Uso',
            'created_at' => '2026-08-10 08:00:00',
            'updated_at' => '2026-08-11 08:00:00',
        ]);

        $user = $this->user('consulta', 'recent-cellphones');

        $this->actingAs($user)->get(route('cellphones.index', ['recent' => 'created']))
            ->assertOk()
            ->assertSee('Último agregado')
            ->assertSeeInOrder(['Registro agregado recientemente', 'Registro actualizado recientemente']);

        $this->get(route('cellphones.index', ['recent' => 'updated']))
            ->assertOk()
            ->assertSee('Último actualizado')
            ->assertSeeInOrder(['Registro actualizado recientemente', 'Registro agregado recientemente']);
    }

    public function test_sensitive_mobile_data_is_restricted_and_whatsapp_message_contains_requested_fields(): void
    {
        $cellphone = Cellphone::create([
            'model' => 'Galaxy S26', 'employee_name_legacy' => 'Persona Prueba', 'status' => 'En Uso',
            'email_account' => 'persona@example.com', 'password' => 'clave-uno',
            'updated_password' => 'clave-dos', 'has_app_lock' => true, 'app_lock_pattern' => '1-5-9',
        ]);
        $consultation = $this->user('consulta');
        $support = $this->user('soporte');

        $this->actingAs($consultation)->get(route('cellphones.show', $cellphone))
            ->assertOk()->assertDontSee('clave-uno')->assertDontSee('Patron');
        $this->actingAs($support)->get(route('cellphones.show', $cellphone))
            ->assertOk()->assertSee('data-pattern-view="1-5-9"', false)->assertSee('pattern-detail-card', false);
        $this->get(route('cellphones.edit', $cellphone))
            ->assertOk()->assertSee('data-pattern-editor', false)->assertSee('value="1-5-9"', false);
        $this->actingAs($this->user('admin', 'mobile-admin'))->get(route('cellphones.show', $cellphone))
            ->assertOk()->assertSee('bi-trash3', false)->assertSee('bi-pencil', false);
        $this->actingAs($consultation)->get(route('cellphones.share', $cellphone))->assertForbidden();
        $response = $this->actingAs($support)->get(route('cellphones.share', $cellphone));
        $response->assertRedirectContains('https://wa.me/');
        $location = rawurldecode($response->headers->get('Location'));
        $this->assertStringContainsString('persona@example.com', $location);
        $this->assertStringContainsString('clave-dos', $location);
        $this->assertStringContainsString('1-5-9', $location);
    }

    public function test_drawing_a_pattern_enables_app_lock_even_if_checkbox_was_not_selected(): void
    {
        $cellphone = Cellphone::create(['model' => 'Telefono con patron', 'status' => 'En Uso']);
        $support = $this->user('soporte', 'pattern-support');

        $this->actingAs($support)->put(route('cellphones.update', $cellphone), [
            'model' => 'Telefono con patron',
            'status' => 'En Uso',
            'has_app_lock' => '0',
            'app_lock_pattern' => '1-2-5-8',
        ])->assertRedirect(route('cellphones.show', $cellphone))->assertSessionHasNoErrors();

        $cellphone->refresh();
        $this->assertTrue($cellphone->has_app_lock);
        $this->assertSame('1-2-5-8', $cellphone->app_lock_pattern);
        $this->get(route('cellphones.show', $cellphone))
            ->assertOk()
            ->assertSee('data-pattern-view="1-2-5-8"', false)
            ->assertSee('Secuencia visual registrada');
    }

    public function test_reassigning_a_cellphone_preserves_the_previous_user_in_history(): void
    {
        $previousEmployee = Employee::create(['full_name' => 'Usuario Anterior', 'department' => 'Ventas', 'status' => 'Activo']);
        $newEmployee = Employee::create(['full_name' => 'Usuario Nuevo', 'department' => 'Sistemas', 'status' => 'Activo']);
        $cellphone = Cellphone::create([
            'model' => 'Honor X6c',
            'employee_id' => $previousEmployee->id,
            'employee_name_legacy' => $previousEmployee->full_name,
            'area' => $previousEmployee->department,
            'status' => 'En Uso',
        ]);

        $response = $this->actingAs($this->user('soporte', 'mobile-assignment-support'))
            ->post(route('cellphones.assignments.store', $cellphone), [
                'employee_id' => $newEmployee->id,
                'date_assigned' => '2026-08-27',
                'condition_on_assign' => 'Bueno',
                'notes' => 'Se entrega con cargador.',
            ]);

        $response->assertRedirect(route('cellphones.show', $cellphone))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        $this->assertDatabaseCount('assignments', 2);
        $this->assertDatabaseHas('assignments', [
            'asset_type' => 'cellphone',
            'asset_id' => $cellphone->id,
            'employee_id' => $previousEmployee->id,
            'assigned_to' => 'Usuario Anterior',
            'date_returned' => '2026-08-27 00:00:00',
        ]);
        $this->assertDatabaseHas('assignments', [
            'asset_type' => 'cellphone',
            'asset_id' => $cellphone->id,
            'employee_id' => $newEmployee->id,
            'assigned_to' => 'Usuario Nuevo',
            'date_returned' => null,
        ]);
        $this->assertSame($newEmployee->id, $cellphone->fresh()->employee_id);
        $this->assertSame('Usuario Nuevo', $cellphone->fresh()->assigned_name);

        $this->get(route('cellphones.show', $cellphone))
            ->assertOk()
            ->assertSee('Nueva asignación')
            ->assertSee('Usuario Anterior')
            ->assertSee('Usuario Nuevo');

        $consultation = $this->user('consulta', 'mobile-assignment-readonly');
        $this->actingAs($consultation)->post(route('cellphones.assignments.store', $cellphone), [
            'employee_id' => $previousEmployee->id,
            'date_assigned' => '2026-08-28',
        ])->assertForbidden();
        $this->assertSame(2, Assignment::count());
    }

    public function test_peripheral_can_link_to_computer_and_employee_in_both_detail_views(): void
    {
        $employee = Employee::create(['full_name' => 'Michelle Silva', 'status' => 'Activo']);
        $computer = HardwareAsset::create(['name' => 'ThinkCentre M70Q', 'status' => 'ENTREGADO']);
        $response = $this->actingAs($this->user('soporte'))->post(route('peripherals.store'), [
            'name' => 'Monitor Lenovo', 'category' => 'Monitor', 'status' => 'Asignado', 'quantity' => 1,
            'employee_id' => $employee->id, 'computer_id' => $computer->id,
        ]);
        $peripheral = Peripheral::firstOrFail();

        $response->assertRedirect(route('peripherals.show', $peripheral));
        $this->actingAs($this->user('consulta', 'consulta-rel'))->get(route('peripherals.show', $peripheral))
            ->assertOk()->assertSee('ThinkCentre M70Q')->assertSee('Michelle Silva');
        $this->get(route('computers.show', $computer))->assertOk()->assertSee('Monitor Lenovo');
    }

    public function test_admin_can_upload_download_and_delete_an_asset_file(): void
    {
        Storage::fake('local');
        $admin = $this->user('admin');
        $cellphone = Cellphone::create(['model' => 'Equipo documental', 'status' => 'En Uso']);

        $this->actingAs($admin)->post(route('asset-files.store', ['cellphone', $cellphone->id]), [
            'file' => UploadedFile::fake()->create('factura.pdf', 50, 'application/pdf'),
            'label' => 'Factura',
        ])->assertRedirect();

        $file = AssetFile::firstOrFail();
        Storage::disk('local')->assertExists(substr($file->file_path, strlen('laravel-local:')));
        $this->get(route('asset-files.download', $file))->assertDownload('factura.pdf');
        $this->delete(route('asset-files.destroy', $file))->assertRedirect();
        $this->assertDatabaseMissing('asset_files', ['id' => $file->id]);
    }

    private function user(string $role, ?string $username = null): User
    {
        return User::create([
            'username' => $username ?: 'user_'.$role.'_'.User::count(),
            'password' => Hash::make('password'),
            'full_name' => 'Usuario '.$role,
            'role' => $role,
        ]);
    }
}
