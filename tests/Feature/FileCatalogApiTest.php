<?php

namespace Tests\Feature;

use App\Models\FileCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, ?string $username = null): User
    {
        return User::create([
            'username' => $username ?? ('user_' . $role . '_' . User::count()),
            'password' => Hash::make('password'),
            'name' => 'Usuario ' . $role,
            'role' => $role,
        ]);
    }

    public function test_user_can_list_and_search_archivos_generales(): void
    {
        $admin = $this->user('admin');
        $consulta = $this->user('consulta');

        FileCatalog::create([
            'original_name' => 'manual_instalacion.pdf',
            'alias_name' => 'Manual de Redes',
            'file_path' => 'laravel-local:library/files/test1.pdf',
            'file_size' => 1048576,
            'uploaded_by' => $admin->id,
            'comments' => 'Documento principal',
        ]);

        FileCatalog::create([
            'original_name' => 'drivers_printer.zip',
            'alias_name' => 'Controladores HP',
            'file_path' => 'laravel-local:library/files/test2.zip',
            'file_size' => 2097152,
            'uploaded_by' => $admin->id,
            'comments' => 'Software de soporte',
        ]);

        $res = $this->actingAs($consulta, 'sanctum')->getJson('/api/v1/archivos-generales?search=Controladores');
        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $res->assertJsonCount(1, 'data');
        $res->assertJsonPath('data.0.alias_name', 'Controladores HP');
    }

    public function test_user_can_view_archivo_general_details_without_file_path(): void
    {
        $admin = $this->user('admin');
        $consulta = $this->user('consulta');

        $file = FileCatalog::create([
            'original_name' => 'diagrama_red.png',
            'alias_name' => 'Topologia de Red',
            'file_path' => 'laravel-local:library/files/topologia.png',
            'file_size' => 524288,
            'uploaded_by' => $admin->id,
            'comments' => 'Esquema de rack principal',
        ]);

        $res = $this->actingAs($consulta, 'sanctum')->getJson('/api/v1/archivos-generales/' . $file->id);
        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('data.display_name', 'Topologia de Red');
        $res->assertJsonPath('data.original_name', 'diagrama_red.png');
        $res->assertJsonMissing(['file_path', 'laravel-local', 'library/files']);
    }

    public function test_admin_or_soporte_can_upload_archivo_general(): void
    {
        Storage::fake('local');
        $soporte = $this->user('soporte');

        $file = UploadedFile::fake()->create('guia_soporte.pdf', 500, 'application/pdf');

        $res = $this->actingAs($soporte, 'sanctum')->postJson('/api/v1/archivos-generales', [
            'alias_name' => 'Guia de Soporte Tecnico',
            'comments' => 'Archivo util para nuevos empleados',
            'file' => $file,
        ]);
        $res->assertStatus(201);
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('data.original_name', 'guia_soporte.pdf');
        $res->assertJsonPath('data.alias_name', 'Guia de Soporte Tecnico');

        $this->assertDatabaseHas('ftp_catalog', [
            'original_name' => 'guia_soporte.pdf',
            'alias_name' => 'Guia de Soporte Tecnico',
            'uploaded_by' => $soporte->id,
        ]);
    }

    public function test_admin_or_soporte_can_update_alias_name_and_comments(): void
    {
        $soporte = $this->user('soporte');

        $file = FileCatalog::create([
            'original_name' => 'nota.txt',
            'alias_name' => 'Nombre Viejo',
            'file_path' => 'laravel-local:library/files/nota.txt',
            'file_size' => 100,
            'uploaded_by' => $soporte->id,
            'comments' => 'Sin comentarios',
        ]);

        $res = $this->actingAs($soporte, 'sanctum')->putJson('/api/v1/archivos-generales/' . $file->id, [
            'alias_name' => 'Nombre Nuevo Actualizado',
            'comments' => 'Comentario actualizado',
        ]);
        $res->assertStatus(200);
        $res->assertJsonPath('success', true);
        $res->assertJsonPath('data.alias_name', 'Nombre Nuevo Actualizado');

        $this->assertDatabaseHas('ftp_catalog', [
            'id' => $file->id,
            'alias_name' => 'Nombre Nuevo Actualizado',
            'comments' => 'Comentario actualizado',
        ]);
    }

    public function test_admin_can_delete_archivo_general(): void
    {
        Storage::fake('local');
        $admin = $this->user('admin');

        $uploaded = UploadedFile::fake()->create('temporal.docx', 100);
        $path = $uploaded->storeAs('library/files', 'temporal.docx', 'local');

        $file = FileCatalog::create([
            'original_name' => 'temporal.docx',
            'alias_name' => 'Borrador',
            'file_path' => 'laravel-local:' . $path,
            'file_size' => 100,
            'uploaded_by' => $admin->id,
        ]);

        $res = $this->actingAs($admin, 'sanctum')->deleteJson('/api/v1/archivos-generales/' . $file->id);
        $res->assertStatus(200);
        $res->assertJsonPath('success', true);

        $this->assertDatabaseMissing('ftp_catalog', ['id' => $file->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_consulta_user_cannot_upload_update_or_delete(): void
    {
        $admin = $this->user('admin');
        $consulta = $this->user('consulta');

        $file = FileCatalog::create([
            'original_name' => 'protegido.pdf',
            'file_path' => 'laravel-local:library/files/prot.pdf',
            'uploaded_by' => $admin->id,
        ]);

        $fakeFile = UploadedFile::fake()->create('test.pdf', 10);

        $res1 = $this->actingAs($consulta, 'sanctum')->postJson('/api/v1/archivos-generales', ['file' => $fakeFile]);
        $res1->assertStatus(403);

        $res2 = $this->actingAs($consulta, 'sanctum')->putJson('/api/v1/archivos-generales/' . $file->id, ['alias_name' => 'Hack']);
        $res2->assertStatus(403);

        $res3 = $this->actingAs($consulta, 'sanctum')->deleteJson('/api/v1/archivos-generales/' . $file->id);
        $res3->assertStatus(403);
    }

    public function test_soporte_user_cannot_delete(): void
    {
        $admin = $this->user('admin');
        $soporte = $this->user('soporte');

        $file = FileCatalog::create([
            'original_name' => 'protegido.pdf',
            'file_path' => 'laravel-local:library/files/prot.pdf',
            'uploaded_by' => $admin->id,
        ]);

        $res = $this->actingAs($soporte, 'sanctum')->deleteJson('/api/v1/archivos-generales/' . $file->id);
        $res->assertStatus(403);
    }

    public function test_user_can_download_and_preview_archivo_general(): void
    {
        Storage::fake('local');
        $admin = $this->user('admin');
        $consulta = $this->user('consulta');

        $uploaded = UploadedFile::fake()->create('reporte.pdf', 200, 'application/pdf');
        $path = $uploaded->storeAs('library/files', 'reporte.pdf', 'local');

        $file = FileCatalog::create([
            'original_name' => 'reporte.pdf',
            'alias_name' => 'Reporte Mensual',
            'file_path' => 'laravel-local:' . $path,
            'file_size' => 200,
            'uploaded_by' => $admin->id,
        ]);

        $resDownload = $this->actingAs($consulta, 'sanctum')->get('/api/v1/archivos-generales/' . $file->id . '/descargar');
        $resDownload->assertStatus(200);
        $resDownload->assertHeader('content-disposition', 'attachment; filename=reporte.pdf');

        $resPreview = $this->actingAs($consulta, 'sanctum')->get('/api/v1/archivos-generales/' . $file->id . '/vista');
        $resPreview->assertStatus(200);
    }
}
