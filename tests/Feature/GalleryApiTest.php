<?php

namespace Tests\Feature;

use App\Models\GalleryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryApiTest extends TestCase
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

    public function test_list_gallery_does_not_contain_filename_and_omits_audit_logs(): void
    {
        GalleryItem::create([
            'title' => 'Fotografía de Servidores',
            'notes' => 'Rack principal',
            'filename' => 'laravel-local:library/gallery/test1.jpg',
            'upload_date' => now(),
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/galeria');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id', 'title', 'notes', 'extension', 'file_size',
                        'formatted_file_size', 'upload_date', 'created_at',
                        'thumbnail_url', 'image_url', 'download_url',
                    ]
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total']
            ])
            ->assertJsonMissing(['filename'])
            ->assertJsonMissing(['audit_logs']);
    }

    public function test_detail_gallery_does_not_contain_filename(): void
    {
        $item = GalleryItem::create([
            'title' => 'Switch Core HP',
            'notes' => 'Panel frontal',
            'filename' => 'laravel-local:library/gallery/test2.jpg',
            'upload_date' => now(),
        ]);

        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->getJson("/api/v1/galeria/{$item->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Switch Core HP')
            ->assertJsonMissing(['filename']);
    }

    public function test_audit_logs_only_returned_for_admin_in_detail(): void
    {
        $item = GalleryItem::create([
            'title' => 'Prueba Auditoria',
            'filename' => 'laravel-local:library/gallery/test_audit.jpg',
            'upload_date' => now(),
        ]);

        $adminResp = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/galeria/{$item->id}");
        $adminResp->assertStatus(200)
            ->assertJsonStructure(['data' => ['audit_logs']]);

        $soporteResp = $this->actingAs($this->soporteUser, 'sanctum')
            ->getJson("/api/v1/galeria/{$item->id}");
        $soporteResp->assertStatus(200)
            ->assertJsonMissing(['audit_logs']);
    }

    public function test_search_filters_gallery_items_by_title_or_notes(): void
    {
        GalleryItem::create([
            'title' => 'Cámara de Seguridad Entrada',
            'notes' => 'Lente exterior',
            'filename' => 'laravel-local:library/gallery/cam1.jpg',
        ]);
        GalleryItem::create([
            'title' => 'Router Mikrotik',
            'notes' => 'Oficina central',
            'filename' => 'laravel-local:library/gallery/router.jpg',
        ]);

        $response = $this->actingAs($this->consultaUser, 'sanctum')
            ->getJson('/api/v1/galeria?search=Mikrotik');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Router Mikrotik');
    }

    public function test_soporte_user_can_create_gallery_item_with_valid_image(): void
    {
        $file = UploadedFile::fake()->image('evidencia.jpg', 600, 600);

        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->postJson('/api/v1/galeria', [
                'title' => 'Nueva Evidencia Fotográfica',
                'notes' => 'Fotos del mantenimiento prevenivo',
                'image' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Nueva Evidencia Fotográfica');

        $this->assertDatabaseHas('gallery', [
            'title' => 'Nueva Evidencia Fotográfica',
        ]);
    }

    public function test_non_image_file_returns_422(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->postJson('/api/v1/galeria', [
                'title' => 'Documento Invalido',
                'image' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_oversized_image_returns_422(): void
    {
        // Fake file > 15MB (16000 KB)
        $file = UploadedFile::fake()->create('huge_photo.jpg', 16000, 'image/jpeg');

        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->postJson('/api/v1/galeria', [
                'title' => 'Foto Gigante',
                'image' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_consulta_user_cannot_create_or_update_gallery_item(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $createResponse = $this->actingAs($this->consultaUser, 'sanctum')
            ->postJson('/api/v1/galeria', [
                'title' => 'Intento de Creacion',
                'image' => $file,
            ]);
        $createResponse->assertStatus(403);

        $item = GalleryItem::create([
            'title' => 'Existente',
            'filename' => 'laravel-local:library/gallery/ex.jpg',
        ]);

        $updateResponse = $this->actingAs($this->consultaUser, 'sanctum')
            ->putJson("/api/v1/galeria/{$item->id}", [
                'title' => 'Intento de Edicion',
            ]);
        $updateResponse->assertStatus(403);
    }

    public function test_soporte_user_can_update_item_and_replace_image_safely(): void
    {
        $oldFile = UploadedFile::fake()->image('old_photo.png');
        $oldPath = $oldFile->storeAs('library/gallery', 'old_photo.png', 'local');

        $item = GalleryItem::create([
            'title' => 'Foto Antigua',
            'notes' => 'Notas viejas',
            'filename' => 'laravel-local:' . $oldPath,
        ]);

        Storage::disk('local')->assertExists('library/gallery/old_photo.png');

        $newFile = UploadedFile::fake()->image('new_photo.png');

        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->postJson("/api/v1/galeria/{$item->id}", [
                '_method' => 'PUT',
                'title' => 'Foto Nueva Actualizada',
                'notes' => 'Notas actualizadas',
                'image' => $newFile,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Foto Nueva Actualizada');

        // Verify old file was safely deleted
        Storage::disk('local')->assertMissing('library/gallery/old_photo.png');
    }

    public function test_soporte_user_cannot_delete_gallery_item(): void
    {
        $item = GalleryItem::create([
            'title' => 'Para borrar',
            'filename' => 'laravel-local:library/gallery/del.jpg',
        ]);

        $response = $this->actingAs($this->soporteUser, 'sanctum')
            ->deleteJson("/api/v1/galeria/{$item->id}");

        $response->assertStatus(403);
    }

    public function test_admin_user_can_delete_gallery_item_and_physical_file(): void
    {
        $fakePath = UploadedFile::fake()->image('to_delete.jpg')
            ->storeAs('library/gallery', 'to_delete.jpg', 'local');

        $item = GalleryItem::create([
            'title' => 'Imagen Borrable por Admin',
            'filename' => 'laravel-local:' . $fakePath,
        ]);

        Storage::disk('local')->assertExists('library/gallery/to_delete.jpg');

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/galeria/{$item->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('gallery', ['id' => $item->id]);
        Storage::disk('local')->assertMissing('library/gallery/to_delete.jpg');
    }

    public function test_authenticated_user_can_access_image_thumbnail_and_download(): void
    {
        $fakePath = UploadedFile::fake()->image('photo.jpg', 800, 600)
            ->storeAs('library/gallery', 'photo.jpg', 'local');

        $item = GalleryItem::create([
            'title' => 'Imagen Completa',
            'filename' => 'laravel-local:' . $fakePath,
        ]);

        $imgResp = $this->actingAs($this->consultaUser, 'sanctum')
            ->get("/api/v1/galeria/{$item->id}/imagen");
        $imgResp->assertStatus(200);

        $thumbResp = $this->actingAs($this->consultaUser, 'sanctum')
            ->get("/api/v1/galeria/{$item->id}/thumbnail");
        $thumbResp->assertStatus(200);

        $dlResp = $this->actingAs($this->consultaUser, 'sanctum')
            ->get("/api/v1/galeria/{$item->id}/descargar");
        $dlResp->assertStatus(200);
    }
}