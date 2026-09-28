<?php

namespace Tests\Feature;

use App\Models\Cellphone;
use App\Models\Employee;
use App\Models\HardwareAsset;
use App\Models\OutlookAccount;
use App\Models\Peripheral;
use App\Models\Printer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeAndGlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_can_create_employee_and_new_record_is_immediately_searchable(): void
    {
        $support = $this->user('soporte');
        $response = $this->actingAs($support)->post(route('employees.store'), [
            'full_name' => 'Ulises Inventario Nuevo', 'department' => 'Sistemas',
            'position' => 'Analista TI', 'email_corporate' => 'ulises.nuevo@example.com',
            'extension' => '4821', 'status' => 'Activo',
        ]);
        $employee = Employee::firstOrFail();
        $response->assertRedirect(route('employees.show', $employee));

        $searchResponse = $this->getJson(route('search.suggestions', ['q' => '4821']))
            ->assertOk()->assertJsonPath('results.0.type', 'Empleado')
            ->assertJsonPath('results.0.url', route('employees.show', $employee));
        $this->assertStringContainsString('no-store', $searchResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', $searchResponse->headers->get('Cache-Control'));
    }

    public function test_renaming_employee_updates_all_legacy_assignment_names(): void
    {
        $employee = Employee::create(['full_name' => 'Nombre Anterior', 'status' => 'Activo']);
        $computer = HardwareAsset::create(['name' => 'Equipo Relacionado', 'status' => 'ENTREGADO', 'employee_id' => $employee->id, 'assigned_user' => 'Nombre Anterior']);
        $cellphone = Cellphone::create(['model' => 'Telefono Relacionado', 'status' => 'En Uso', 'employee_id' => $employee->id, 'employee_name_legacy' => 'Nombre Anterior']);
        $peripheral = Peripheral::create(['name' => 'Monitor Relacionado', 'status' => 'Asignado', 'quantity' => 1, 'employee_id' => $employee->id, 'assigned_to' => 'Nombre Anterior']);
        $printer = Printer::create(['name' => 'Impresora Relacionada', 'status' => 'Activo', 'employee_id' => $employee->id, 'assigned_to' => 'Nombre Anterior']);

        $this->actingAs($this->user('soporte'))->put(route('employees.update', $employee), [
            'full_name' => 'Nombre Actualizado', 'status' => 'Activo',
        ])->assertRedirect(route('employees.show', $employee));

        $this->assertSame('Nombre Actualizado', $computer->fresh()->assigned_user);
        $this->assertSame('Nombre Actualizado', $cellphone->fresh()->employee_name_legacy);
        $this->assertSame('Nombre Actualizado', $peripheral->fresh()->assigned_to);
        $this->assertSame('Nombre Actualizado', $printer->fresh()->assigned_to);
    }

    public function test_global_search_finds_each_migrated_type_and_links_to_its_detail(): void
    {
        $this->actingAs($this->user('consulta'));
        $computer = HardwareAsset::create(['name' => 'Equipo Zenith', 'serial' => 'SER-ZENITH', 'status' => 'DISPONIBLE']);
        $cellphone = Cellphone::create(['model' => 'Movil Zenith', 'phone_number' => '3311122233', 'status' => 'En Uso']);
        $peripheral = Peripheral::create(['name' => 'Teclado Zenith', 'code' => 'PER-ZENITH', 'status' => 'Disponible', 'quantity' => 1]);
        $employee = Employee::create(['full_name' => 'Empleado Zenith', 'status' => 'Activo']);
        $printer = Printer::create(['name' => 'Impresora Zenith', 'status' => 'Activo']);

        $response = $this->getJson(route('search.suggestions', ['q' => 'Zenith']))->assertOk();
        $urls = collect($response->json('results'))->pluck('url');
        $this->assertTrue($urls->contains(route('computers.show', $computer)));
        $this->assertTrue($urls->contains(route('cellphones.show', $cellphone)));
        $this->assertTrue($urls->contains(route('peripherals.show', $peripheral)));
        $this->assertTrue($urls->contains(route('employees.show', $employee)));
        $this->assertTrue($urls->contains(route('printers.show', $printer)));
    }

    public function test_consultation_role_cannot_modify_employees(): void
    {
        $employee = Employee::create(['full_name' => 'Empleado Protegido', 'status' => 'Activo']);
        $user = $this->user('consulta');
        $this->actingAs($user)->get(route('employees.create'))->assertForbidden();
        $this->put(route('employees.update', $employee), ['full_name' => 'Cambio', 'status' => 'Activo'])->assertForbidden();
        $this->delete(route('employees.destroy', $employee))->assertForbidden();
    }

    public function test_employee_api_detail_includes_all_related_assets_without_secrets(): void
    {
        $employee = Employee::create(['full_name' => 'Empleado con activos', 'status' => 'Activo']);
        HardwareAsset::create(['name' => 'Laptop Uno', 'code' => 'PC-01', 'status' => 'ENTREGADO', 'employee_id' => $employee->id]);
        Cellphone::create(['model' => 'Teléfono Uno', 'phone_number' => '5551234', 'status' => 'En Uso', 'employee_id' => $employee->id]);
        Peripheral::create(['name' => 'Monitor Uno', 'code' => 'PER-01', 'status' => 'Asignado', 'quantity' => 1, 'employee_id' => $employee->id]);
        Printer::create(['name' => 'Impresora Uno', 'ip_address' => '192.168.1.20', 'status' => 'Activo', 'employee_id' => $employee->id]);
        OutlookAccount::create([
            'correo' => 'empleado@example.com',
            "contrase\u{00F1}a" => 'secreto-que-no-debe-salir',
            'estatus' => 'ACTIVA',
            'employee_id' => $employee->id,
        ]);

        $response = $this->actingAs($this->user('consulta'), 'sanctum')
            ->getJson(route('api.v1.empleados.show', $employee))
            ->assertOk()
            ->assertJsonCount(5, 'data.related_assets')
            ->assertJsonPath('data.related_assets.0.type', 'computer')
            ->assertJsonPath('data.related_assets.1.type', 'cellphone')
            ->assertJsonPath('data.related_assets.2.type', 'peripheral')
            ->assertJsonPath('data.related_assets.3.type', 'printer')
            ->assertJsonPath('data.related_assets.4.type', 'outlook');

        $response->assertJsonMissing(['secreto-que-no-debe-salir']);
    }

    private function user(string $role): User
    {
        return User::create(['username' => 'employee_'.$role.'_'.User::count(), 'password' => Hash::make('password'), 'full_name' => 'Usuario '.$role, 'role' => $role]);
    }
}
