<?php

namespace Tests\Feature\Api;

use App\Models\Assignment;
use App\Models\Employee;
use App\Models\HardwareAsset;
use App\Models\User;
use App\Services\AssetAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssetAssignmentParityAndConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_and_api_routes_produce_identical_business_rules_and_db_state_on_assignment(): void
    {
        $user = User::create([
            'username' => 'support_user',
            'password' => Hash::make('password'),
            'role' => 'soporte',
        ]);
        $token = $user->createToken('MobileApp')->plainTextToken;

        $employeeWeb = Employee::create(['full_name' => 'Empleado Web', 'department' => 'Sistemas', 'status' => 'Activo']);
        $employeeApi = Employee::create(['full_name' => 'Empleado API', 'department' => 'Ventas', 'status' => 'Activo']);

        $computerWeb = HardwareAsset::create(['name' => 'Equipo Web Test', 'status' => 'DISPONIBLE']);
        $computerApi = HardwareAsset::create(['name' => 'Equipo API Test', 'status' => 'DISPONIBLE']);

        // 1. Web Assignment HTTP request
        $this->actingAs($user)->post(route('computers.assignments.store', $computerWeb), [
            'employee_id' => $employeeWeb->id,
            'date_assigned' => '2026-08-19',
            'condition_on_assign' => 'Excelente',
        ])->assertRedirect(route('computers.show', $computerWeb));

        // 2. API Assignment HTTP request
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/computadoras/{$computerApi->id}/asignar", [
                'employee_id' => $employeeApi->id,
                'date_assigned' => '2026-08-19',
                'condition_on_assign' => 'Excelente',
            ])->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify DB State Parity
        $this->assertDatabaseHas('hardware_assets', [
            'id' => $computerWeb->id,
            'employee_id' => $employeeWeb->id,
            'status' => 'ENTREGADO',
        ]);

        $this->assertDatabaseHas('hardware_assets', [
            'id' => $computerApi->id,
            'employee_id' => $employeeApi->id,
            'status' => 'ENTREGADO',
        ]);

        $this->assertDatabaseHas('assignments', [
            'asset_type' => 'inventory',
            'asset_id' => $computerWeb->id,
            'employee_id' => $employeeWeb->id,
            'condition_on_assign' => 'Excelente',
        ]);

        $this->assertDatabaseHas('assignments', [
            'asset_type' => 'inventory',
            'asset_id' => $computerApi->id,
            'employee_id' => $employeeApi->id,
            'condition_on_assign' => 'Excelente',
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'assign', 'entity' => 'assignments']);
    }

    public function test_web_and_api_routes_produce_identical_db_state_on_return(): void
    {
        $user = User::create(['username' => 'support_user', 'password' => Hash::make('password'), 'role' => 'soporte']);
        $token = $user->createToken('MobileApp')->plainTextToken;

        $employee = Employee::create(['full_name' => 'Empleado Dev', 'status' => 'Activo']);

        $compWeb = HardwareAsset::create(['name' => 'Comp Web Return', 'status' => 'ENTREGADO', 'employee_id' => $employee->id]);
        $assignWeb = Assignment::create([
            'asset_type' => 'inventory', 'asset_id' => $compWeb->id,
            'employee_id' => $employee->id, 'date_assigned' => '2026-08-01',
        ]);

        $compApi = HardwareAsset::create(['name' => 'Comp API Return', 'status' => 'ENTREGADO', 'employee_id' => $employee->id]);
        $assignApi = Assignment::create([
            'asset_type' => 'inventory', 'asset_id' => $compApi->id,
            'employee_id' => $employee->id, 'date_assigned' => '2026-08-01',
        ]);

        // Web Return
        $this->actingAs($user)->put(route('computers.assignments.return', [$compWeb, $assignWeb]), [
            'date_returned' => '2026-08-19',
            'condition_on_return' => 'Bueno',
        ])->assertSessionHas('success');

        // API Return
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/computadoras/{$compApi->id}/devolver/{$assignApi->id}", [
                'date_returned' => '2026-08-19',
                'condition_on_return' => 'Bueno',
            ])->assertStatus(200)
            ->assertJsonPath('success', true);

        // Assert Both Computers are Released to DISPONIBLE and employee_id is null
        $this->assertDatabaseHas('hardware_assets', ['id' => $compWeb->id, 'status' => 'DISPONIBLE', 'employee_id' => null]);
        $this->assertDatabaseHas('hardware_assets', ['id' => $compApi->id, 'status' => 'DISPONIBLE', 'employee_id' => null]);
    }

    public function test_assigning_already_delivered_computer_fails_validation_after_lock(): void
    {
        $user = User::create(['username' => 'support_user', 'password' => Hash::make('password'), 'role' => 'soporte']);
        $employee = Employee::create(['full_name' => 'Empleado A', 'status' => 'Activo']);
        $employee2 = Employee::create(['full_name' => 'Empleado B', 'status' => 'Activo']);

        $computer = HardwareAsset::create(['name' => 'Equipo Concurrente', 'status' => 'DISPONIBLE']);

        $service = app(AssetAssignmentService::class);

        // First assignment succeeds
        $service->assignComputer($computer->id, ['date_assigned' => '2026-08-19'], $employee, $user);

        // Second assignment attempt on the locked & updated computer must fail
        $this->expectException(ValidationException::class);
        $service->assignComputer($computer->id, ['date_assigned' => '2026-08-19'], $employee2, $user);
    }

    public function test_returning_already_returned_assignment_fails_validation_after_lock(): void
    {
        $user = User::create(['username' => 'support_user', 'password' => Hash::make('password'), 'role' => 'soporte']);
        $employee = Employee::create(['full_name' => 'Empleado C', 'status' => 'Activo']);

        $computer = HardwareAsset::create(['name' => 'Equipo Devolución Doble', 'status' => 'ENTREGADO', 'employee_id' => $employee->id]);
        $assignment = Assignment::create([
            'asset_type' => 'inventory', 'asset_id' => $computer->id,
            'employee_id' => $employee->id, 'date_assigned' => '2026-08-10',
        ]);

        $service = app(AssetAssignmentService::class);

        // First return succeeds
        $service->returnComputer($computer->id, $assignment->id, ['date_returned' => '2026-08-19'], $user);

        // Second return attempt must fail
        $this->expectException(ValidationException::class);
        $service->returnComputer($computer->id, $assignment->id, ['date_returned' => '2026-08-19'], $user);
    }
}
