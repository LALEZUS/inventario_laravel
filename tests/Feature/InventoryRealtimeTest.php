<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InventoryRealtimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_stream_returns_inventory_events_and_no_cache_headers(): void
    {
        $event = $this->event('printers', 'update', 17);

        $response = $this->actingAs($this->user())->get(route('inventory-events.stream', ['since' => 0]));

        $response->assertOk()->assertHeader('content-type', 'text/event-stream; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('event: inventory-update', $content);
        $this->assertStringContainsString('id: '.$event->id, $content);
        $this->assertStringContainsString('"entity":"printers"', $content);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_stream_ignores_session_events_and_respects_last_event_id(): void
    {
        $this->event('users', 'login', 1);
        $first = $this->event('inks', 'create', 10);
        $second = $this->event('toner', 'update', 4);

        $response = $this->actingAs($this->user())->withHeader('Last-Event-ID', (string) $first->id)
            ->get(route('inventory-events.stream', ['since' => 0]));
        $content = $response->streamedContent();

        $this->assertStringNotContainsString('"entity":"users"', $content);
        $this->assertStringNotContainsString('id: '.$first->id."\n", $content);
        $this->assertStringContainsString('id: '.$second->id."\n", $content);
    }

    public function test_guest_cannot_open_stream_and_layout_enables_live_client(): void
    {
        $this->get(route('inventory-events.stream'))->assertRedirect(route('login'));
        $this->get(route('inventory-events.poll'))->assertRedirect(route('login'));

        $this->actingAs($this->user())->get(route('dashboard'))
            ->assertOk()->assertSee('live-connection-status')->assertSee('new EventSource', false)
            ->assertDontSee('http:\/\/localhost:8001\/eventos\/inventario', false);
    }

    public function test_stream_includes_network_changes_without_sensitive_values(): void
    {
        AuditLog::create([
            'user_name' => 'Operador Red', 'role' => 'soporte', 'action' => 'update',
            'entity' => 'watchguard_users', 'entity_id' => 8,
            'after_data' => ['username' => 'vpn-user', 'password' => '[PROTECTED]'],
            'ip_address' => '192.168.15.99',
        ]);
        $content = $this->actingAs($this->user())->get(route('inventory-events.stream', ['since' => 0]))->streamedContent();

        $this->assertStringContainsString('"entity":"watchguard_users"', $content);
        $this->assertStringNotContainsString('password', $content);
        $this->assertStringNotContainsString('vpn-user', $content);
    }

    public function test_poll_transport_returns_events_without_holding_the_request(): void
    {
        $event = $this->event('network_devices', 'create', 90);

        $response = $this->actingAs($this->user())->getJson(route('inventory-events.poll', ['since' => 0]));
        $response->assertOk()
            ->assertJsonPath('cursor', $event->id)
            ->assertJsonPath('events.0.entity', 'network_devices')
            ->assertJsonPath('events.0.entity_id', '90');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    private function event(string $entity, string $action, int $entityId): AuditLog
    {
        return AuditLog::create([
            'user_name' => 'Operador Remoto',
            'role' => 'soporte',
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'after_data' => ['name' => 'Dato seguro'],
            'ip_address' => '192.168.15.99',
        ]);
    }

    private function user(): User
    {
        return User::create([
            'username' => 'realtime_'.User::count(),
            'password' => Hash::make('password'),
            'full_name' => 'Usuario Tiempo Real',
            'role' => 'soporte',
        ]);
    }
}
