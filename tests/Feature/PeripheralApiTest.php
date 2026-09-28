<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\HardwareAsset;
use App\Models\Peripheral;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PeripheralApiTest extends TestCase
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

    public function test_can_list_peripherals_with_pagination_and_eager_loading(): void
    {
        $admin = $this->user('admin');
        $employee = Employee::create(['full_name' => 'Carlos Mendoza', 'status' => 'Activo']);
        $computer = HardwareAsset::create(['name' => 'Dell Workstation', 'status' => 'Disponible']);

        Peripheral::create([
            'name' => 'Teclado Mecánico',
            'brand' => 'Logitech',
            'category' => 'Teclado',
            'status' => 'Asignado',
            'employee_id' => $employee->id,
            'assigned_to' => $employee->full_name,
            'computer_id' => $computer->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/perifericos');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'code', 'name', 'brand', 'model', 'serial',
                        'category', 'status', 'location', 'quantity', 'comments',
                        'assigned_to', 'computer_id', 'employee_id',
                        'employee' => ['id', 'full_name', 'department'],
                        'computer' => ['id', 'code', 'name'],
                    ],
                ],
                'meta' => [
                    'pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages'],
                ],
            ]);
    }

    public function test_can_filter_peripherals_by_search_status_category(): void
    {
        $admin = $this->user('admin');
        Peripheral::create(['name' => 'Mouse Ergonómico', 'status' => 'Disponible', 'category' => 'Mouse']);
        Peripheral::create(['name' => 'Monitor 4K', 'status' => 'Asignado', 'category' => 'Monitor']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/perifericos?search=Mouse&status=Disponible&category=Mouse');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Mouse Ergonómico', $response->json('data.0.name'));
    }

    public function test_can_create_peripheral_and_syncs_assigned_to_from_employee(): void
    {
        $support = $this->user('soporte');
        $employee = Employee::create(['full_name' => 'María López', 'status' => 'Activo']);
        $computer = HardwareAsset::create(['name' => 'HP ZBook', 'status' => 'Disponible']);

        $payload = [
            'name' => 'Headset Inalámbrico',
            'brand' => 'Jabra',
            'model' => 'Evolve2 65',
            'category' => 'Audio',
            'status' => 'Asignado',
            'quantity' => 1,
            'employee_id' => $employee->id,
            'computer_id' => $computer->id,
        ];

        $response = $this->actingAs($support, 'sanctum')
            ->postJson('/api/v1/perifericos', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Headset Inalámbrico')
            ->assertJsonPath('data.assigned_to', 'María López')
            ->assertJsonPath('data.employee.id', $employee->id)
            ->assertJsonPath('data.computer.id', $computer->id);

        $this->assertDatabaseHas('peripherals', [
            'name' => 'Headset Inalámbrico',
            'assigned_to' => 'María López',
            'employee_id' => $employee->id,
            'computer_id' => $computer->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'entity' => 'peripherals',
            'action' => 'create',
        ]);
    }

    public function test_supports_simultaneous_employee_and_computer_assignment(): void
    {
        $admin = $this->user('admin');
        $employee = Employee::create(['full_name' => 'Pedro Sánchez', 'status' => 'Activo']);
        $computer = HardwareAsset::create(['name' => 'Lenovo ThinkPad', 'status' => 'Entregado']);

        $payload = [
            'name' => 'Monitor Secundario',
            'brand' => 'Dell',
            'category' => 'Monitor',
            'status' => 'Asignado',
            'quantity' => 1,
            'employee_id' => $employee->id,
            'computer_id' => $computer->id,
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/perifericos', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.employee_id', $employee->id)
            ->assertJsonPath('data.computer_id', $computer->id);
    }

    public function test_employee_change_syncs_assigned_to(): void
    {
        $admin = $this->user('admin');
        $emp1 = Employee::create(['full_name' => 'Empleado Uno', 'status' => 'Activo']);
        $emp2 = Employee::create(['full_name' => 'Empleado Dos', 'status' => 'Activo']);

        $peripheral = Peripheral::create([
            'name' => 'Teclado USB',
            'status' => 'Asignado',
            'employee_id' => $emp1->id,
            'assigned_to' => $emp1->full_name,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/perifericos/{$peripheral->id}", [
                'name' => 'Teclado USB',
                'status' => 'Asignado',
                'quantity' => 1,
                'employee_id' => $emp2->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.assigned_to', 'Empleado Dos')
            ->assertJsonPath('data.employee_id', $emp2->id);

        $this->assertDatabaseHas('peripherals', [
            'id' => $peripheral->id,
            'assigned_to' => 'Empleado Dos',
            'employee_id' => $emp2->id,
        ]);
    }

    public function test_only_admin_can_delete_peripheral(): void
    {
        $support = $this->user('soporte');
        $admin = $this->user('admin');
        $peripheral = Peripheral::create(['name' => 'Webcam HD', 'status' => 'Disponible']);

        // Soporte debe recibir 403 Forbidden
        $this->actingAs($support, 'sanctum')
            ->deleteJson("/api/v1/perifericos/{$peripheral->id}")
            ->assertStatus(403);

        // Admin puede eliminar exitosamente (200)
        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/perifericos/{$peripheral->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('peripherals', ['id' => $peripheral->id]);
    }

    public function test_validations_return_422_json(): void
    {
        $admin = $this->user('admin');
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/perifericos', [
                // Faltan name, quantity, status
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'quantity', 'status']);
    }
}