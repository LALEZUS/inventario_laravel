<?php

namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\HardwareAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HardwareAssetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_consultation_user_can_list_computers_and_view_details_without_secret_passwords(): void
    {
        $consultationUser = User::create([
            'username' => 'consulta_user',
            'password' => Hash::make('password'),
            'role' => 'consulta',
        ]);
        $token = $consultationUser->createToken('Mobile')->plainTextToken;

        $computer = HardwareAsset::create([
            'name' => 'Computadora Privada',
            'brand' => 'HP',
            'status' => 'DISPONIBLE',
            'admin_password' => 'CLAVE_SUPER_SECRETA_123',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/computadoras/{$computer->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Computadora Privada')
            ->assertJsonPath('data.brand', 'HP');

        // Directives 4 & 5: Ensure secrets like admin_password are NEVER present in standard detail Resources
        $this->assertArrayNotHasKey('admin_password', $response->json('data'));
        $this->assertStringNotContainsString('CLAVE_SUPER_SECRETA_123', $response->getContent());
    }

    public function test_consultation_user_cannot_create_or_delete_computers_via_api(): void
    {
        $consultationUser = User::create(['username' => 'consulta_user', 'password' => Hash::make('password'), 'role' => 'consulta']);
        $token = $consultationUser->createToken('Mobile')->plainTextToken;

        $computer = HardwareAsset::create(['name' => 'Equipo Test', 'status' => 'DISPONIBLE']);

        // Create attempt forbidden
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/computadoras', ['name' => 'Nuevo Equipo'])
            ->assertStatus(403);

        // Delete attempt forbidden
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/computadoras/{$computer->id}")
            ->assertStatus(403);
    }

    public function test_support_user_can_create_and_update_computers_but_cannot_delete(): void
    {
        $supportUser = User::create(['username' => 'support_user', 'password' => Hash::make('password'), 'role' => 'soporte']);
        $token = $supportUser->createToken('Mobile')->plainTextToken;

        // Create
        $storeResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/computadoras', [
                'name' => 'PC Soporte',
                'brand' => 'Lenovo',
                'status' => 'DISPONIBLE',
            ]);

        $storeResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'PC Soporte');

        $id = $storeResponse->json('data.id');

        // Update
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson("/api/v1/computadoras/{$id}", [
                'name' => 'PC Soporte Editada',
                'status' => 'DISPONIBLE',
            ])->assertStatus(200)
            ->assertJsonPath('data.name', 'PC Soporte Editada');

        // Delete forbidden for suporte
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/computadoras/{$id}")
            ->assertStatus(403);
    }

    public function test_support_can_assign_an_employee_while_creating_a_computer(): void
    {
        $supportUser = User::create(['username' => 'support_assign', 'password' => Hash::make('password'), 'role' => 'soporte']);
        $employee = Employee::create([
            'full_name' => 'Empleado desde formulario móvil',
            'department' => 'Operaciones',
            'status' => 'Activo',
        ]);

        $response = $this->actingAs($supportUser, 'sanctum')
            ->postJson('/api/v1/computadoras', [
                'name' => 'Laptop asignada desde alta',
                'format' => 'Laptop / Notebook',
                'status' => 'ENTREGADO',
                'employee_id' => $employee->id,
                'delivery_date' => '2026-08-28',
            ])
            ->assertCreated()
            ->assertJsonPath('data.employee.id', $employee->id)
            ->assertJsonPath('data.employee.full_name', $employee->full_name)
            ->assertJsonPath('data.delivery_date', '2026-08-28');

        $this->assertDatabaseHas('hardware_assets', [
            'id' => $response->json('data.id'),
            'employee_id' => $employee->id,
            'status' => 'ENTREGADO',
        ]);
    }

    public function test_admin_user_can_delete_computers(): void
    {
        $adminUser = User::create(['username' => 'admin_user', 'password' => Hash::make('password'), 'role' => 'admin']);
        $token = $adminUser->createToken('Mobile')->plainTextToken;

        $computer = HardwareAsset::create(['name' => 'PC para borrar', 'status' => 'DISPONIBLE']);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/computadoras/{$computer->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('hardware_assets', ['id' => $computer->id]);
    }
}
