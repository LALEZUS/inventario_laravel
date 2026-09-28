<?php

namespace Tests\Feature;

use App\Models\Cellphone;
use App\Models\Employee;
use App\Models\HardwareAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_search_returns_grouped_results_with_ids_and_no_secrets(): void
    {
        $user = User::create([
            'username' => 'tester',
            'password' => Hash::make('password'),
            'full_name' => 'Usuario Test',
            'role' => 'consulta',
        ]);

        $computer = HardwareAsset::create([
            'name' => 'DELL LATITUDE 5420',
            'brand' => 'Dell',
            'model' => 'Latitude 5420',
            'serial' => 'SN-DELL-5420',
            'status' => 'ENTREGADO',
        ]);

        $cellphone = Cellphone::create([
            'model' => 'Samsung Dell Pro',
            'phone_number' => '555-999-1111',
            'status' => 'En Uso',
            'password' => 'SECRET_PASSWORD_123',
            'app_lock_password' => 'SECRET_PIN_456',
        ]);

        $employee = Employee::create([
            'full_name' => 'Dellman Pérez',
            'department' => 'Sistemas',
            'position' => 'Analista TI',
            'status' => 'Activo',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/buscar?q=dell')
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'query',
                    'total_matches',
                    'results' => [
                        'computers' => ['label', 'count', 'items' => [['id', 'title', 'subtitle', 'status']]],
                        'cellphones' => ['label', 'count', 'items' => [['id', 'title', 'subtitle', 'status']]],
                        'employees' => ['label', 'count', 'items' => [['id', 'title', 'subtitle', 'status']]],
                    ],
                ],
            ]);

        $this->assertEquals(true, $response->json('success'));
        $this->assertEquals('dell', $response->json('data.query'));
        $this->assertEquals(3, $response->json('data.total_matches'));

        // Verify ID matching
        $this->assertEquals($computer->id, $response->json('data.results.computers.items.0.id'));
        $this->assertEquals('ENTREGADO', $response->json('data.results.computers.items.0.status'));

        $this->assertEquals($cellphone->id, $response->json('data.results.cellphones.items.0.id'));
        $this->assertEquals('En Uso', $response->json('data.results.cellphones.items.0.status'));

        $this->assertEquals($employee->id, $response->json('data.results.employees.items.0.id'));

        // Verify absence of sensitive data in response body string
        $content = $response->getContent();
        $this->assertStringNotContainsString('SECRET_PASSWORD_123', $content);
        $this->assertStringNotContainsString('SECRET_PIN_456', $content);
    }

    public function test_api_search_with_query_less_than_two_chars_returns_consistent_empty_groups(): void
    {
        $user = User::create([
            'username' => 'tester2',
            'password' => Hash::make('password'),
            'full_name' => 'Usuario Test 2',
            'role' => 'consulta',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/buscar?q=d')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'query' => 'd',
                    'total_matches' => 0,
                    'results' => [
                        'computers' => [
                            'label' => 'Computadoras',
                            'count' => 0,
                            'items' => [],
                        ],
                        'cellphones' => [
                            'label' => 'Celulares',
                            'count' => 0,
                            'items' => [],
                        ],
                        'employees' => [
                            'label' => 'Empleados',
                            'count' => 0,
                            'items' => [],
                        ],
                    ],
                ],
            ]);
    }
}