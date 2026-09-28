<?php

namespace Tests\Feature;

use App\Models\AssetFile;
use App\Models\Employee;
use App\Models\Ink;
use App\Models\Printer;
use App\Models\Toner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrinterManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_can_create_network_printer_with_employee_and_supplies(): void
    {
        $employee = Employee::create(['full_name' => 'Responsable Impresion', 'status' => 'Activo']);
        $ink = Ink::create(['brand' => 'Epson', 'type' => '544', 'color' => 'BK', 'quantity' => 3]);
        $toner = Toner::create(['brand' => 'Ricoh', 'model' => 'IM 430', 'quantity' => 2]);

        $response = $this->actingAs($this->user('soporte'))->post(route('printers.store'), [
            'name' => 'Impresora Sistemas',
            'brand' => 'Epson',
            'model' => 'EcoTank L3250',
            'status' => 'Activo',
            'is_network' => '1',
            'ip_address' => '192.168.15.220',
            'zone' => 'Sistemas',
            'employee_id' => $employee->id,
            'supply_type' => 'Mixto',
            'ink_type' => '544 / IM 430',
            'linked_inks' => [$ink->id],
            'linked_toner' => [$toner->id],
        ]);

        $printer = Printer::firstOrFail();
        $response->assertSessionHasNoErrors()->assertRedirect(route('printers.show', $printer));
        $this->assertTrue($printer->is_network);
        $this->assertSame('Responsable Impresion', $printer->assigned_to);
        $this->assertSame([$ink->id], $printer->linkedInkIds());
        $this->assertSame([$toner->id], $printer->linkedTonerIds());
        $this->assertDatabaseHas('audit_logs', ['entity' => 'printers', 'entity_id' => (string) $printer->id, 'action' => 'create']);

        $this->get(route('printers.show', $printer))->assertOk()
            ->assertSee('192.168.15.220')->assertSee('Epson 544')->assertSee('Ricoh')
            ->assertSee('ink-color-black', false);
    }

    public function test_printer_search_filters_and_global_search_find_current_data(): void
    {
        $this->actingAs($this->user('consulta'));
        $printer = Printer::create([
            'name' => 'Plotter Proyectos Zenith', 'brand' => 'Canon', 'model' => 'TX-3100',
            'ip_address' => '192.168.15.230', 'is_network' => true, 'status' => 'Mantenimiento',
        ]);
        Printer::create(['name' => 'Impresora Local', 'is_network' => false, 'status' => 'Activo']);

        $this->get(route('printers.index', ['search' => 'Zenith', 'status' => 'Mantenimiento', 'network' => '1']))
            ->assertOk()->assertSee('Plotter Proyectos Zenith')->assertDontSee('Impresora Local');
        $results = $this->getJson(route('search.suggestions', ['q' => 'TX-3100']))
            ->assertOk()->json('results');
        $this->assertTrue(collect($results)->pluck('url')->contains(route('printers.show', $printer)));
    }

    public function test_printer_supports_private_files_and_role_permissions(): void
    {
        Storage::fake('local');
        $printer = Printer::create(['name' => 'Impresora Documental', 'status' => 'Activo']);
        $support = $this->user('soporte');

        $this->actingAs($support)->post(route('asset-files.store', ['printer', $printer->id]), [
            'file' => UploadedFile::fake()->create('manual.pdf', 40, 'application/pdf'),
            'label' => 'Manual tecnico',
        ])->assertRedirect();
        $file = AssetFile::firstOrFail();
        $this->get(route('asset-files.download', $file))->assertDownload('manual.pdf');
        $this->delete(route('printers.destroy', $printer))->assertForbidden();

        $consultation = $this->user('consulta');
        $this->actingAs($consultation)->get(route('printers.create'))->assertForbidden();
        $this->put(route('printers.update', $printer), ['name' => 'Cambio', 'status' => 'Activo'])->assertForbidden();
    }

    private function user(string $role): User
    {
        return User::create([
            'username' => 'printer_'.$role.'_'.User::count(),
            'password' => Hash::make('password'),
            'full_name' => 'Usuario '.$role,
            'role' => $role,
        ]);
    }
}
