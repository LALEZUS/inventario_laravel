<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NoteApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $soporteUser;
    protected User $consultaUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'username' => 'admin_user',
            'full_name' => 'Admin User',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->soporteUser = User::create([
            'username' => 'soporte_user',
            'full_name' => 'Soporte User',
            'password' => Hash::make('password'),
            'role' => 'soporte',
        ]);

        $this->consultaUser = User::create([
            'username' => 'consulta_user',
            'full_name' => 'Consulta User',
            'password' => Hash::make('password'),
            'role' => 'consulta',
        ]);
    }

    public function test_list_notes_omits_audit_logs(): void
    {
        Note::create([
            'title' => 'Nota de Prueba',
            'content' => 'Contenido de prueba',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/notas');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'title', 'content', 'created_at', 'updated_at']
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total']
            ])
            ->assertJsonMissing(['audit_logs']);
    }

    public function test_search_notes_by_title_and_content(): void
    {
        Note::create([
            'title' => 'Servidor BD Principal',
            'content' => 'Dirección IP: 192.168.1.10',
        ]);
        Note::create([
            'title' => 'Mantenimiento de Red',
            'content' => 'Revisión de switches en Rack B',
        ]);

        // Search by title
        $responseTitle = $this->actingAs($this->consultaUser, 'sanctum')
            ->getJson('/api/v1/notas?search=Servidor');
        $responseTitle->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Servidor BD Principal');

        // Search by content
        $responseContent = $this->actingAs($this->consultaUser, 'sanctum')
            ->getJson('/api/v1/notas?search=switches');
        $responseContent->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Mantenimiento de Red');
    }

    public function test_note_with_null_content_is_handled_safely(): void
    {
        $note = Note::create([
            'title' => 'Nota sin contenido',
            'content' => null,
        ]);

        $response = $this->actingAs($this->consultaUser, 'sanctum')
            ->getJson("/api/v1/notas/{$note->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Nota sin contenido')
            ->assertJsonPath('data.content', null);
    }

    public function test_multiline_text_content_is_preserved_exactly(): void
    {
        $multilineText = "Línea 1: Requerimientos\nLínea 2: Paso 1\nLínea 3: Paso 2\nLínea 4: Conclusión";

        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->postJson('/api/v1/notas', [
                'title' => 'Procedimiento Multilínea',
                'content' => $multilineText,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.content', $multilineText);

        $this->assertDatabaseHas('notes', [
            'title' => 'Procedimiento Multilínea',
            'content' => $multilineText,
        ]);
    }

    public function test_consulta_user_cannot_create_update_or_delete_note(): void
    {
        $createResp = $this->actingAs($this->consultaUser, 'sanctum')
            ->postJson('/api/v1/notas', [
                'title' => 'Intento Creación Consulta',
            ]);
        $createResp->assertStatus(403);

        $note = Note::create([
            'title' => 'Nota Existente',
            'content' => 'Prueba',
        ]);

        $updateResp = $this->actingAs($this->consultaUser, 'sanctum')
            ->putJson("/api/v1/notas/{$note->id}", [
                'title' => 'Intento Edición Consulta',
            ]);
        $updateResp->assertStatus(403);

        $deleteResp = $this->actingAs($this->consultaUser, 'sanctum')
            ->deleteJson("/api/v1/notas/{$note->id}");
        $deleteResp->assertStatus(403);
    }

    public function test_soporte_user_can_create_and_update_note_but_cannot_delete(): void
    {
        $createResp = $this->actingAs($this->soporteUser, 'sanctum')
            ->postJson('/api/v1/notas', [
                'title' => 'Nota de Soporte',
                'content' => 'Contenido inicial',
            ]);
        $createResp->assertStatus(201)
            ->assertJsonPath('data.title', 'Nota de Soporte');

        $noteId = $createResp->json('data.id');

        $updateResp = $this->actingAs($this->soporteUser, 'sanctum')
            ->putJson("/api/v1/notas/{$noteId}", [
                'title' => 'Nota de Soporte Editada',
                'content' => 'Contenido actualizado',
            ]);
        $updateResp->assertStatus(200)
            ->assertJsonPath('data.title', 'Nota de Soporte Editada');

        $deleteResp = $this->actingAs($this->soporteUser, 'sanctum')
            ->deleteJson("/api/v1/notas/{$noteId}");
        $deleteResp->assertStatus(403);
    }

    public function test_admin_user_can_delete_note(): void
    {
        $note = Note::create([
            'title' => 'Nota para Borrar',
            'content' => 'Por Admin',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/notas/{$note->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    public function test_audit_logs_only_returned_for_admin_in_detail(): void
    {
        $note = Note::create([
            'title' => 'Nota Auditable',
            'content' => 'Prueba auditoría',
        ]);

        $adminResp = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/notas/{$note->id}");
        $adminResp->assertStatus(200)
            ->assertJsonStructure(['data' => ['audit_logs']]);

        $soporteResp = $this->actingAs($this->soporteUser, 'sanctum')
            ->getJson("/api/v1/notas/{$note->id}");
        $soporteResp->assertStatus(200)
            ->assertJsonMissing(['audit_logs']);
    }
}