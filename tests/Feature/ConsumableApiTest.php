<?php

namespace Tests\Feature;

use App\Models\Ink;
use App\Models\Printer;
use App\Models\Toner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConsumableApiTest extends TestCase
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

    public function test_stats_calculates_real_database_metrics_correctly(): void
    {
        $admin = $this->user('admin');

        Ink::create(['brand' => 'Epson', 'model' => 'T504', 'color' => 'Negro', 'type' => 'Botella', 'quantity' => 0, 'status' => 'Agotado']);
        Ink::create(['brand' => 'Canon', 'model' => 'GI-190', 'color' => 'Cian', 'type' => 'Botella', 'quantity' => 2, 'status' => 'Bajo']);
        Ink::create(['brand' => 'HP', 'model' => '664', 'color' => 'Negro', 'type' => 'Cartucho', 'quantity' => 10, 'status' => 'Disponible']);

        Toner::create(['brand' => 'HP', 'model' => '58A', 'quantity' => 0, 'status' => 'AGOTADO']);
        Toner::create(['brand' => 'Brother', 'model' => 'TN660', 'quantity' => 5, 'status' => 'DISPONIBLE']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/consumibles/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.ink_types', 3)
            ->assertJsonPath('data.ink_units', 12)
            ->assertJsonPath('data.toner_types', 2)
            ->assertJsonPath('data.toner_units', 5)
            ->assertJsonPath('data.low_stock', 3); // Ink q=0, Ink q=2, Toner q=0 (all <= 2)
    }

    public function test_can_create_ink_with_quantity_zero_and_quantity_two(): void
    {
        $support = $this->user('soporte');

        $res1 = $this->actingAs($support, 'sanctum')
            ->postJson('/api/v1/consumibles/tintas', [
                'brand' => 'Epson',
                'model' => 'T504',
                'color' => 'Negro',
                'type' => 'Botella',
                'quantity' => 0,
                'status' => 'Agotado',
            ]);

        $res1->assertStatus(201)->assertJsonPath('data.quantity', 0);

        $res2 = $this->actingAs($support, 'sanctum')
            ->postJson('/api/v1/consumibles/tintas', [
                'brand' => 'Epson',
                'model' => 'T504',
                'color' => 'Cian',
                'type' => 'Botella',
                'quantity' => 2,
                'status' => 'Bajo',
            ]);

        $res2->assertStatus(201)->assertJsonPath('data.quantity', 2);
    }

    public function test_can_create_toner_with_quantity_zero(): void
    {
        $admin = $this->user('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/consumibles/toner', [
                'brand' => 'HP',
                'model' => '85A',
                'quantity' => 0,
                'status' => 'AGOTADO',
                'comments' => 'Tóner agotado en almacén',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.quantity', 0)
            ->assertJsonPath('data.comments', 'Tóner agotado en almacén');
    }

    public function test_negative_quantity_returns_422_validation_error(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/consumibles/tintas', [
                'brand' => 'Epson',
                'color' => 'Negro',
                'type' => 'Botella',
                'quantity' => -5,
                'status' => 'Disponible',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_edit_quantity_updates_stock_correctly(): void
    {
        $admin = $this->user('admin');
        $ink = Ink::create([
            'brand' => 'HP',
            'color' => 'Negro',
            'type' => 'Cartucho',
            'quantity' => 5,
            'status' => 'Disponible',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/consumibles/tintas/{$ink->id}", [
                'brand' => 'HP',
                'color' => 'Negro',
                'type' => 'Cartucho',
                'quantity' => 12,
                'status' => 'Disponible',
            ]);

        $response->assertStatus(200)->assertJsonPath('data.quantity', 12);
        $this->assertDatabaseHas('inks', ['id' => $ink->id, 'quantity' => 12]);
    }

    public function test_deleting_ink_unlinks_id_from_all_printers(): void
    {
        $admin = $this->user('admin');
        $ink = Ink::create(['brand' => 'Epson', 'color' => 'Negro', 'type' => 'Botella', 'quantity' => 3, 'status' => 'Disponible']);
        $printer = Printer::create(['name' => 'Impresora Epson L3150', 'status' => 'Activo', 'linked_inks' => [$ink->id, 999]]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/consumibles/tintas/{$ink->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('inks', ['id' => $ink->id]);
        $printer->refresh();
        $this->assertEquals([999], $printer->linked_inks);
    }

    public function test_deleting_toner_unlinks_id_from_all_printers(): void
    {
        $admin = $this->user('admin');
        $toner = Toner::create(['brand' => 'HP', 'model' => '12A', 'quantity' => 4, 'status' => 'NUEVO']);
        $printer = Printer::create(['name' => 'Impresora HP 1020', 'status' => 'Activo', 'linked_toner' => [$toner->id, 888]]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/consumibles/toner/{$toner->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('toner', ['id' => $toner->id]);
        $printer->refresh();
        $this->assertEquals([888], $printer->linked_toner);
    }

    public function test_support_user_cannot_delete_consumables(): void
    {
        $support = $this->user('soporte');
        $ink = Ink::create(['brand' => 'Brother', 'color' => 'Negro', 'type' => 'Botella', 'quantity' => 1, 'status' => 'Disponible']);
        $toner = Toner::create(['brand' => 'Brother', 'model' => 'TN450', 'quantity' => 1, 'status' => 'NUEVO']);

        $this->actingAs($support, 'sanctum')->deleteJson("/api/v1/consumibles/tintas/{$ink->id}")->assertStatus(403);
        $this->actingAs($support, 'sanctum')->deleteJson("/api/v1/consumibles/toner/{$toner->id}")->assertStatus(403);
    }

    public function test_consultation_user_cannot_edit_consumables(): void
    {
        $consulta = $this->user('consulta');
        $ink = Ink::create(['brand' => 'Canon', 'color' => 'Negro', 'type' => 'Botella', 'quantity' => 1, 'status' => 'Disponible']);

        $this->actingAs($consulta, 'sanctum')
            ->putJson("/api/v1/consumibles/tintas/{$ink->id}", ['brand' => 'Canon', 'color' => 'Negro', 'type' => 'Botella', 'quantity' => 10, 'status' => 'Disponible'])
            ->assertStatus(403);
    }

    public function test_admin_can_view_audit_logs_for_consumables(): void
    {
        $admin = $this->user('admin');
        $ink = Ink::create(['brand' => 'Lexmark', 'color' => 'Negro', 'type' => 'Cartucho', 'quantity' => 2, 'status' => 'Disponible']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/consumibles/tintas/{$ink->id}/bitacora");

        $response->assertStatus(200);
    }
}