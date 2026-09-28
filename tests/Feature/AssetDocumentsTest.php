<?php

namespace Tests\Feature;

use App\Models\AssetFile;
use App\Models\HardwareAsset;
use App\Models\User;
use App\Services\AssetQrCode;
use App\Services\ResponsivaGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Tests\TestCase;

class AssetDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_is_a_real_png_and_targets_the_protected_asset_detail(): void
    {
        $computer = HardwareAsset::create(['name' => 'Equipo QR', 'status' => 'DISPONIBLE']);
        $this->actingAs($this->user('consulta'));
        $response = $this->get(route('computers.qr', $computer))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $response->getContent());
        $this->assertGreaterThan(500, strlen($response->getContent()));
        $this->assertSame(route('computers.show', ['computer' => $computer, 'source' => 'qr']), app(AssetQrCode::class)->url($computer));
    }

    public function test_qr_visit_is_audited_only_once_per_five_minutes(): void
    {
        $computer = HardwareAsset::create(['name' => 'Equipo Escaneado', 'status' => 'DISPONIBLE']);
        $this->actingAs($this->user('consulta'));
        $url = route('computers.show', ['computer' => $computer, 'source' => 'qr']);
        $this->get($url)->assertOk();
        $this->get($url)->assertOk();
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertDatabaseHas('audit_logs', ['entity' => 'hardware_assets', 'entity_id' => (string) $computer->id, 'action' => 'qr_scan']);
    }

    public function test_responsiva_omits_empty_values_and_is_saved_as_private_attachment(): void
    {
        Storage::fake('local');
        $computer = HardwareAsset::create([
            'name' => 'ThinkCentre M70Q', 'brand' => 'Lenovo', 'model' => 'ThinkCentre M70Q',
            'processor' => 'Intel Core i5', 'ram' => '16 GB', 'storage' => '512 GB NVMe',
            'serial' => '-', 'code' => null, 'assigned_user' => 'Persona Prueba', 'status' => 'ENTREGADO',
        ]);
        $specifications = app(ResponsivaGenerator::class)->specifications($computer);
        $this->assertArrayNotHasKey('Serie', $specifications);
        $this->assertArrayNotHasKey('Folio de inventario', $specifications);
        $this->assertArrayNotHasKey('Modelo', $specifications);

        $response = $this->actingAs($this->user('soporte'))->post(route('computers.responsiva', $computer));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $file = AssetFile::firstOrFail();
        $this->assertSame('Responsiva firmable - membretada', $file->label);
        $this->assertStringContainsString('Membretada', $file->original_name);
        $this->assertSame('inventory', $file->asset_type);
        Storage::disk('local')->assertExists(substr($file->file_path, strlen('laravel-local:')));
        $this->assertDatabaseHas('audit_logs', ['action' => 'generate_responsiva', 'entity_id' => (string) $computer->id]);
        $this->actingAs($this->user('consulta'))
            ->get(route('asset-files.preview', $file))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $plainResponse = $this->actingAs($this->user('soporte'))->post(route('computers.responsiva', $computer), [
            'letterhead' => '0',
        ]);
        $plainResponse->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $plainFile = AssetFile::query()->latest('id')->firstOrFail();
        $this->assertSame('Responsiva firmable - sin membrete', $plainFile->label);
        $this->assertStringContainsString('Sin_membrete', $plainFile->original_name);
    }

    public function test_consultation_role_cannot_generate_responsiva(): void
    {
        $computer = HardwareAsset::create(['name' => 'Equipo Protegido', 'status' => 'DISPONIBLE']);
        $this->actingAs($this->user('consulta'))->post(route('computers.responsiva', $computer))->assertForbidden();
    }

    public function test_responsiva_preview_supports_larger_photo_layout_without_saving_a_document(): void
    {
        Storage::fake('local');
        $computer = HardwareAsset::create([
            'name' => 'Laptop con evidencia',
            'brand' => 'Lenovo',
            'model' => 'ThinkPad X1',
            'assigned_user' => 'Persona Vista Previa',
            'status' => 'ENTREGADO',
        ]);
        $user = $this->user('soporte');

        $this->actingAs($user)->post(route('asset-files.photos.store', ['assetType' => 'inventory', 'assetId' => $computer->id]), [
            'photos' => [
                UploadedFile::fake()->image('frente.jpg', 900, 1400),
                UploadedFile::fake()->image('reverso.jpg', 900, 1400),
            ],
        ])->assertRedirect();

        $balanced = app(ResponsivaGenerator::class)->generate($computer->fresh(), null, false, 'balanced');
        $large = app(ResponsivaGenerator::class)->generate($computer->fresh(), null, false, 'large');
        $this->assertSame(2, $this->pdfPageCount($balanced));
        $this->assertSame(3, $this->pdfPageCount($large));

        $filesBeforePreview = AssetFile::count();
        $this->post(route('computers.responsiva.preview', $computer), [
            'letterhead' => '0',
            'photo_layout' => 'large',
        ])->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="Vista_previa_responsiva.pdf"');

        $this->assertSame($filesBeforePreview, AssetFile::count());
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'generate_responsiva',
            'entity_id' => (string) $computer->id,
        ]);
    }

    public function test_support_can_upload_view_and_delete_computer_photos(): void
    {
        Storage::fake('local');
        $computer = HardwareAsset::create(['name' => 'Equipo fotografiado', 'status' => 'DISPONIBLE']);

        $this->actingAs($this->user('soporte'))
            ->post(route('asset-files.photos.store', ['assetType' => 'inventory', 'assetId' => $computer->id]), [
                'photos' => [
                    UploadedFile::fake()->image('frente.jpg', 800, 600),
                    UploadedFile::fake()->image('serie.png', 640, 480),
                ],
                'label' => 'Inspeccion inicial',
            ])
            ->assertRedirect();

        $photos = AssetFile::query()->where('asset_type', 'inventory')->get();
        $this->assertCount(2, $photos);
        $this->assertSame('Inspeccion inicial', $photos->first()->label);
        Storage::disk('local')->assertExists(substr($photos->first()->file_path, strlen('laravel-local:')));

        $this->actingAs($this->user('soporte'))
            ->put(route('asset-files.label.update', $photos->first()), ['label' => 'Etiqueta actualizada'])
            ->assertRedirect();
        $this->assertDatabaseHas('asset_files', ['id' => $photos->first()->id, 'label' => 'Etiqueta actualizada']);

        $this->actingAs($this->user('consulta'))
            ->get(route('asset-files.preview', $photos->first()))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
        $deleteResponse = $this->actingAs($this->user('admin'))
            ->delete(route('asset-files.destroy', $photos->first()))
            ->assertRedirect()
            ->assertSessionHas('success', 'Archivo eliminado correctamente.');
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Archivo eliminado correctamente.');
        $this->assertDatabaseCount('asset_files', 1);
    }

    private function pdfPageCount(string $content): int
    {
        $pdf = new Fpdi();

        return $pdf->setSourceFile(StreamReader::createByString($content));
    }

    private function user(string $role): User
    {
        return User::create(['username' => 'docs_'.$role.'_'.User::count(), 'password' => Hash::make('password'), 'full_name' => 'Usuario '.$role, 'role' => $role]);
    }
}
