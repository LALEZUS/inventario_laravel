<?php

namespace Tests\Feature;

use App\Models\Tutorial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TutorialApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $soporteUser;
    protected User $consultaUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

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

    public function test_list_tutorials_does_not_contain_content_url_and_omits_audit_logs(): void
    {
        Tutorial::create([
            'title' => 'Manual de Configuración de VPN',
            'category' => 'Redes',
            'description' => 'Guía paso a paso para cliente WatchGuard',
            'content_url' => 'laravel-local:library/tutorials/vpn_guide.pdf',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/tutoriales');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id', 'title', 'category', 'description', 'comments',
                        'extension', 'file_size', 'formatted_file_size',
                        'has_file', 'created_at', 'updated_at',
                        'preview_url', 'download_url',
                    ]
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total']
            ])
            ->assertJsonMissing(['content_url'])
            ->assertJsonMissing(['audit_logs']);
    }

    public function test_detail_tutorial_does_not_contain_content_url(): void
    {
        $tutorial = Tutorial::create([
            'title' => 'Manual de Impresoras Multifuncionales',
            'category' => 'Impresión',
            'description' => 'Configuración de escáner en red',
            'content_url' => 'laravel-local:library/tutorials/print_guide.pdf',
        ]);

        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->getJson("/api/v1/tutoriales/{$tutorial->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Manual de Impresoras Multifuncionales')
            ->assertJsonMissing(['content_url']);
    }

    public function test_audit_logs_only_returned_for_admin_in_detail(): void
    {
        $tutorial = Tutorial::create([
            'title' => 'Manual de Seguridad TI',
            'content_url' => 'laravel-local:library/tutorials/sec.pdf',
        ]);

        $adminResp = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/tutoriales/{$tutorial->id}");
        $adminResp->assertStatus(200)
            ->assertJsonStructure(['data' => ['audit_logs']]);

        $soporteResp = $this->actingAs($this->soporteUser, 'sanctum')
            ->getJson("/api/v1/tutoriales/{$tutorial->id}");
        $soporteResp->assertStatus(200)
            ->assertJsonMissing(['audit_logs']);
    }

    public function test_search_filters_tutorials_by_title_category_or_description(): void
    {
        Tutorial::create([
            'title' => 'Configuración de Outlook',
            'category' => 'Correo Electrónico',
            'description' => 'Sincronización IMAP/POP3',
            'content_url' => 'laravel-local:library/tutorials/outlook.pdf',
        ]);
        Tutorial::create([
            'title' => 'Mantenimiento de Laptops',
            'category' => 'Hardware',
            'description' => 'Limpieza y pasta térmica',
            'content_url' => 'laravel-local:library/tutorials/laptops.pdf',
        ]);

        $response = $this->actingAs($this->consultaUser, 'sanctum')
            ->getJson('/api/v1/tutoriales?search=Outlook');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Configuración de Outlook');
    }

    public function test_soporte_user_can_create_tutorial_with_valid_pdf(): void
    {
        $file = UploadedFile::fake()->create('guia_redes.pdf', 2048, 'application/pdf');

        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->postJson('/api/v1/tutoriales', [
                'title' => 'Guía de Redes Empresariales',
                'category' => 'Redes',
                'description' => 'Procedimiento de conexión wifi corporativa',
                'comments' => 'Versión 2026',
                'pdf_file' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Guía de Redes Empresariales');

        $this->assertDatabaseHas('tutorials', [
            'title' => 'Guía de Redes Empresariales',
            'category' => 'Redes',
        ]);
    }

    public function test_non_pdf_file_returns_422(): void
    {
        $file = UploadedFile::fake()->create('imagen.png', 1024, 'image/png');

        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->postJson('/api/v1/tutoriales', [
                'title' => 'Tutorial Invalido',
                'pdf_file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pdf_file']);
    }

    public function test_oversized_pdf_greater_than_30mb_returns_422(): void
    {
        // Fake file > 30MB (32000 KB)
        $file = UploadedFile::fake()->create('huge_manual.pdf', 32000, 'application/pdf');

        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->postJson('/api/v1/tutoriales', [
                'title' => 'Manual Gigante',
                'pdf_file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pdf_file']);
    }

    public function test_consulta_user_cannot_create_or_update_tutorial(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 1024, 'application/pdf');

        $createResp = $this->actingAs($this->consultaUser, 'sanctum')
            ->postJson('/api/v1/tutoriales', [
                'title' => 'Intento Creación Consulta',
                'pdf_file' => $file,
            ]);
        $createResp->assertStatus(403);

        $tutorial = Tutorial::create([
            'title' => 'Existente',
            'content_url' => 'laravel-local:library/tutorials/ex.pdf',
        ]);

        $updateResp = $this->actingAs($this->consultaUser, 'sanctum')
            ->putJson("/api/v1/tutoriales/{$tutorial->id}", [
                'title' => 'Intento Edición Consulta',
            ]);
        $updateResp->assertStatus(403);
    }

    public function test_soporte_user_can_update_tutorial_and_replace_pdf_safely(): void
    {
        $oldFile = UploadedFile::fake()->create('old_manual.pdf', 1024, 'application/pdf');
        $oldPath = $oldFile->storeAs('library/tutorials', 'old_manual.pdf', 'local');

        $tutorial = Tutorial::create([
            'title' => 'Manual Antiguo',
            'category' => 'Sistemas',
            'content_url' => 'laravel-local:' . $oldPath,
        ]);

        Storage::disk('local')->assertExists('library/tutorials/old_manual.pdf');

        $newFile = UploadedFile::fake()->create('new_manual.pdf', 2048, 'application/pdf');

        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->postJson("/api/v1/tutoriales/{$tutorial->id}", [
                '_method' => 'PUT',
                'title' => 'Manual Nuevo Actualizado',
                'category' => 'Sistemas Avanzados',
                'pdf_file' => $newFile,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Manual Nuevo Actualizado');

        // Verify old file was safely deleted
        Storage::disk('local')->assertMissing('library/tutorials/old_manual.pdf');
    }

    public function test_soporte_user_cannot_delete_tutorial(): void
    {
        $tutorial = Tutorial::create([
            'title' => 'Para borrar',
            'content_url' => 'laravel-local:library/tutorials/del.pdf',
        ]);

        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->deleteJson("/api/v1/tutoriales/{$tutorial->id}");

        $response->assertStatus(403);
    }

    public function test_admin_user_can_delete_tutorial_and_physical_file(): void
    {
        $fakePath = UploadedFile::fake()->create('to_delete.pdf', 1024, 'application/pdf')
            ->storeAs('library/tutorials', 'to_delete.pdf', 'local');

        $tutorial = Tutorial::create([
            'title' => 'Tutorial Borrable por Admin',
            'content_url' => 'laravel-local:' . $fakePath,
        ]);

        Storage::disk('local')->assertExists('library/tutorials/to_delete.pdf');

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/tutoriales/{$tutorial->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('tutorials', ['id' => $tutorial->id]);
        Storage::disk('local')->assertMissing('library/tutorials/to_delete.pdf');
    }

    public function test_unauthenticated_user_cannot_access_preview_or_download(): void
    {
        $tutorial = Tutorial::create([
            'title' => 'Protegido',
            'content_url' => 'laravel-local:library/tutorials/prot.pdf',
        ]);

        $prevResp = $this->getJson("/api/v1/tutoriales/{$tutorial->id}/ver");
        $prevResp->assertStatus(401);

        $dlResp = $this->getJson("/api/v1/tutoriales/{$tutorial->id}/descargar");
        $dlResp->assertStatus(401);
    }

    public function test_authenticated_user_can_access_preview_and_download(): void
    {
        $fakePath = UploadedFile::fake()->create('doc.pdf', 1024, 'application/pdf')
            ->storeAs('library/tutorials', 'doc.pdf', 'local');

        $tutorial = Tutorial::create([
            'title' => 'Tutorial Descargable',
            'content_url' => 'laravel-local:' . $fakePath,
        ]);

        $prevResp = $this->actingAs($this->consultaUser, 'sanctum')
            ->get("/api/v1/tutoriales/{$tutorial->id}/ver");
        $prevResp->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');

        $dlResp = $this->actingAs($this->consultaUser, 'sanctum')
            ->get("/api/v1/tutoriales/{$tutorial->id}/descargar");
        $dlResp->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }
}