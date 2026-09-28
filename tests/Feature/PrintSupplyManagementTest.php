<?php

namespace Tests\Feature;

use App\Models\Ink;
use App\Models\Printer;
use App\Models\Toner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PrintSupplyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_zero_dates_are_rendered_as_empty_without_corrupting_data(): void
    {
        DB::table('inks')->insert([
            'brand' => 'Canon', 'type' => '190', 'color' => 'BK', 'quantity' => 2,
            'purchase_date' => '0000-00-00', 'expiry_date' => '0000-00-00',
            'status' => 'Disponible', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $ink = Ink::firstOrFail();

        $this->assertNull($ink->dateValue('purchase_date'));
        $this->assertNull($ink->dateValue('expiry_date'));
        $this->actingAs($this->user('consulta'))->get(route('inks.show', $ink))
            ->assertOk()->assertSee('Canon 190')->assertDontSee('-0001');
    }

    public function test_support_can_manage_ink_and_new_value_is_immediately_searchable(): void
    {
        $support = $this->user('soporte');
        $response = $this->actingAs($support)->post(route('inks.store'), [
            'brand' => 'Epson', 'model' => 'EcoTank', 'type' => 'T-Unique-2026',
            'color' => 'Cyan', 'capacity' => '65 ml', 'quantity' => 5,
            'purchase_date' => '2026-08-14', 'expiry_date' => '2029-08-14',
            'status' => 'Disponible', 'comments' => 'Consumible de prueba',
        ]);
        $ink = Ink::firstOrFail();

        $response->assertSessionHasNoErrors()->assertRedirect(route('inks.show', $ink));
        $this->getJson(route('search.suggestions', ['q' => 'T-Unique-2026']))
            ->assertOk()->assertJsonPath('results.0.url', route('inks.show', $ink));
        $this->put(route('inks.update', $ink), [
            'brand' => 'Epson', 'type' => 'T-Unique-2026', 'color' => 'Cyan',
            'quantity' => 1, 'status' => 'Bajo',
        ])->assertRedirect(route('inks.show', $ink));
        $this->assertSame(1, $ink->fresh()->quantity);
        $this->get(route('supplies.index', ['type' => 'inks']))
            ->assertOk()
            ->assertSee('ink-color-cyan', false);
        $this->assertDatabaseHas('audit_logs', ['entity' => 'inks', 'action' => 'update']);
    }

    public function test_toner_crud_preserves_legacy_comments_and_removes_printer_link_on_delete(): void
    {
        $admin = $this->user('admin');
        $toner = Toner::create([
            'brand' => 'Ricoh', 'model' => 'SP-Unique', 'quantity' => 2,
            'status' => 'NUEVO', 'comentarios' => 'Comentario heredado',
        ]);
        $printer = Printer::create([
            'name' => 'Impresora vinculada', 'status' => 'Activo', 'linked_toner' => [$toner->id],
        ]);

        $this->actingAs($admin)->get(route('toner.show', $toner))
            ->assertOk()->assertSee('Comentario heredado')->assertSee('Impresora vinculada');
        $this->delete(route('toner.destroy', $toner))->assertRedirect(route('supplies.index', ['type' => 'toner']));
        $this->assertSame([], $printer->fresh()->linkedTonerIds());
        $this->assertDatabaseMissing('toner', ['id' => $toner->id]);
    }

    public function test_consultation_role_cannot_modify_supplies(): void
    {
        $ink = Ink::create(['brand' => 'Epson', 'type' => '544', 'color' => 'BK', 'quantity' => 1, 'status' => 'Bajo']);
        $toner = Toner::create(['brand' => 'Brother', 'model' => '1060', 'quantity' => 1, 'status' => 'BAJO']);
        $this->actingAs($this->user('consulta'));

        $this->get(route('inks.create'))->assertForbidden();
        $this->put(route('inks.update', $ink), ['brand' => 'X', 'type' => 'X', 'color' => 'X', 'quantity' => 1, 'status' => 'Bajo'])->assertForbidden();
        $this->get(route('toner.create'))->assertForbidden();
        $this->delete(route('toner.destroy', $toner))->assertForbidden();
    }

    private function user(string $role): User
    {
        return User::create([
            'username' => 'supply_'.$role.'_'.User::count(),
            'password' => Hash::make('password'),
            'full_name' => 'Usuario '.$role,
            'role' => $role,
        ]);
    }
}
