<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Ink;
use App\Models\Printer;
use App\Models\Toner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PrinterApiTest extends TestCase
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

    public function test_can_list_printers_with_pagination_and_eager_loading(): void
    {
        $admin = $this->user('admin');
        $employee = Employee::create(['full_name' => 'Laura Gómez', 'status' => 'Activo']);

        Printer::create([
            'name' => 'HP LaserJet Pro',
            'brand' => 'HP',
            'model' => 'M404dn',
            'is_network' => true,
            'ip_address' => '192.168.1.100',
            'status' => 'Activo',
            'employee_id' => $employee->id,
            'assigned_to' => $employee->full_name,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/impresoras');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'name', 'brand', 'model', 'serial', 'code',
                        'ip_address', 'zone', 'assigned_to', 'is_network',
                        'supply_type', 'ink_type', 'linked_inks', 'linked_toner',
                        'status', 'comments', 'employee_id',
                        'employee' => ['id', 'full_name', 'department'],
                    ],
                ],
                'meta' => [
                    'pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages'],
                ],
            ]);
    }

    public function test_network_printer_stores_ip_address(): void
    {
        $support = $this->user('soporte');

        $payload = [
            'name' => 'Epson WorkForce Pro',
            'brand' => 'Epson',
            'model' => 'WF-C5790',
            'is_network' => true,
            'ip_address' => '192.168.15.50',
            'status' => 'Activo',
        ];

        $response = $this->actingAs($support, 'sanctum')
            ->postJson('/api/v1/impresoras', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_network', true)
            ->assertJsonPath('data.ip_address', '192.168.15.50');

        $this->assertDatabaseHas('printers', [
            'name' => 'Epson WorkForce Pro',
            'is_network' => 1,
            'ip_address' => '192.168.15.50',
        ]);
    }

    public function test_non_network_printer_cleans_ip_address_to_null(): void
    {
        $admin = $this->user('admin');

        $payload = [
            'name' => 'Canon Pixma USB',
            'brand' => 'Canon',
            'model' => 'G3110',
            'is_network' => false,
            'ip_address' => '192.168.1.200', // IP enviada que debe limpiarse
            'status' => 'Activo',
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/impresoras', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_network', false)
            ->assertJsonPath('data.ip_address', null);

        $this->assertDatabaseHas('printers', [
            'name' => 'Canon Pixma USB',
            'is_network' => 0,
            'ip_address' => null,
        ]);
    }

    public function test_employee_change_syncs_assigned_to(): void
    {
        $admin = $this->user('admin');
        $emp1 = Employee::create(['full_name' => 'Ana Torres', 'status' => 'Activo']);
        $emp2 = Employee::create(['full_name' => 'Roberto Díaz', 'status' => 'Activo']);

        $printer = Printer::create([
            'name' => 'Brother HL-L2350DW',
            'status' => 'Activo',
            'employee_id' => $emp1->id,
            'assigned_to' => $emp1->full_name,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/impresoras/{$printer->id}", [
                'name' => 'Brother HL-L2350DW',
                'status' => 'Activo',
                'employee_id' => $emp2->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.assigned_to', 'Roberto Díaz')
            ->assertJsonPath('data.employee_id', $emp2->id);

        $this->assertDatabaseHas('printers', [
            'id' => $printer->id,
            'assigned_to' => 'Roberto Díaz',
            'employee_id' => $emp2->id,
        ]);
    }

    public function test_stores_linked_inks_and_linked_toner_correctly(): void
    {
        $admin = $this->user('admin');
        $ink = Ink::create(['brand' => 'Epson', 'model' => 'T504', 'color' => 'Negro']);
        $toner = Toner::create(['brand' => 'HP', 'model' => '58A']);

        $payload = [
            'name' => 'Multifuncional Mixta',
            'status' => 'Activo',
            'linked_inks' => [$ink->id],
            'linked_toner' => [$toner->id],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/impresoras', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.linked_inks.0', $ink->id)
            ->assertJsonPath('data.linked_toner.0', $toner->id);

        $printerId = $response->json('data.id');

        $detailResponse = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/impresoras/{$printerId}");

        $detailResponse->assertStatus(200)
            ->assertJsonPath('data.linked_inks_detail.0.brand', 'Epson')
            ->assertJsonPath('data.linked_toner_detail.0.brand', 'HP');
    }

    public function test_consultation_user_cannot_create_or_update_printer(): void
    {
        $consulta = $this->user('consulta');
        $printer = Printer::create(['name' => 'Impresora Pública', 'status' => 'Activo']);

        $this->actingAs($consulta, 'sanctum')
            ->postJson('/api/v1/impresoras', ['name' => 'Nueva', 'status' => 'Activo'])
            ->assertStatus(403);

        $this->actingAs($consulta, 'sanctum')
            ->putJson("/api/v1/impresoras/{$printer->id}", ['name' => 'Modificada', 'status' => 'Activo'])
            ->assertStatus(403);
    }

    public function test_support_user_cannot_delete_printer(): void
    {
        $support = $this->user('soporte');
        $printer = Printer::create(['name' => 'Impresora Área Comercial', 'status' => 'Activo']);

        $this->actingAs($support, 'sanctum')
            ->deleteJson("/api/v1/impresoras/{$printer->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_view_audit_logs(): void
    {
        $admin = $this->user('admin');
        $printer = Printer::create(['name' => 'Impresora Auditoría', 'status' => 'Activo']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/impresoras/{$printer->id}/bitacora");

        $response->assertStatus(200);
    }

    public function test_no_internal_secrets_or_physical_paths_in_resources(): void
    {
        $admin = $this->user('admin');
        $printer = Printer::create(['name' => 'Impresora Segura', 'status' => 'Activo']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/impresoras/{$printer->id}");

        $response->assertStatus(200);
        $json = json_encode($response->json());

        $this->assertStringNotContainsString('password', $json);
        $this->assertStringNotContainsString('/storage/app/', $json);
    }
}