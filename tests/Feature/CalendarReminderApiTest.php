<?php

namespace Tests\Feature;

use App\Models\CalendarReminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CalendarReminderApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $support;

    private User $readOnly;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->user('agenda_admin', 'admin');
        $this->support = $this->user('agenda_support', 'soporte');
        $this->readOnly = $this->user('agenda_read', 'consulta');
    }

    public function test_index_returns_selected_month_and_upcoming_events(): void
    {
        CalendarReminder::create($this->payload('2026-08-10', 'Revisión de equipos'));
        CalendarReminder::create($this->payload('2026-09-02', 'Evento de otro mes'));

        $response = $this->actingAs($this->readOnly, 'sanctum')
            ->getJson('/api/v1/agenda?month=2026-08');

        $response->assertOk()
            ->assertJsonPath('meta.month', '2026-08')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Revisión de equipos')
            ->assertJsonStructure(['meta' => ['upcoming']]);
    }

    public function test_support_can_create_and_update_but_cannot_delete(): void
    {
        $created = $this->actingAs($this->support, 'sanctum')
            ->postJson('/api/v1/agenda', $this->payload('2026-08-28', 'Inventario mensual'));

        $created->assertCreated()->assertJsonPath('data.title', 'Inventario mensual');
        $id = $created->json('data.id');

        $updated = $this->actingAs($this->support, 'sanctum')
            ->putJson("/api/v1/agenda/{$id}", [
                ...$this->payload('2026-08-29', 'Inventario actualizado'),
                'is_done' => true,
            ]);

        $updated->assertOk()
            ->assertJsonPath('data.event_date', '2026-08-29')
            ->assertJsonPath('data.is_done', true);

        $this->actingAs($this->support, 'sanctum')
            ->deleteJson("/api/v1/agenda/{$id}")
            ->assertForbidden();
    }

    public function test_read_only_user_cannot_create_and_admin_can_delete(): void
    {
        $this->actingAs($this->readOnly, 'sanctum')
            ->postJson('/api/v1/agenda', $this->payload('2026-08-28', 'No permitido'))
            ->assertForbidden();

        $item = CalendarReminder::create($this->payload('2026-08-28', 'Para eliminar'));

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/agenda/{$item->id}")
            ->assertOk();

        $this->assertDatabaseMissing('calendar_reminders', ['id' => $item->id]);
    }

    /** @return array<string, mixed> */
    private function payload(string $date, string $title): array
    {
        return [
            'event_date' => $date,
            'title' => $title,
            'color' => '#E31B23',
            'description' => 'Actividad programada',
            'comments' => null,
            'is_done' => false,
        ];
    }

    private function user(string $username, string $role): User
    {
        return User::create([
            'username' => $username,
            'full_name' => $username,
            'password' => Hash::make('password'),
            'role' => $role,
        ]);
    }
}
