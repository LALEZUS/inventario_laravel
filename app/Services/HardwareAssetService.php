<?php

namespace App\Services;

use App\Models\HardwareAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HardwareAssetService
{
    private const BOOLEAN_FIELDS = ['has_office', 'has_winrar', 'has_reader', 'has_server', 'has_printer'];

    public function __construct(
        private readonly NfoParser $parser,
        private readonly AuditLogger $audit
    ) {}

    /**
     * Store a new HardwareAsset with transaction & NFO handling.
     */
    public function store(array $validatedData, ?UploadedFile $nfoFile, User $performer): HardwareAsset
    {
        $data = $this->prepareData($validatedData, $nfoFile);
        $storedNfo = $data['nfo_file'] ?? null;

        try {
            return DB::transaction(function () use ($data, $performer) {
                $computer = HardwareAsset::create($data);
                $this->audit->record('create', 'hardware_assets', $computer->id, null, $computer->getAttributes(), $performer);

                return $computer;
            });
        } catch (Throwable $exception) {
            if ($storedNfo && str_starts_with($storedNfo, 'nfo/')) {
                Storage::disk('local')->delete($storedNfo);
            }
            throw $exception;
        }
    }

    /**
     * Update an existing HardwareAsset with concurrency protection.
     */
    public function update(int $computerId, array $validatedData, ?UploadedFile $nfoFile, User $performer): HardwareAsset
    {
        $data = $this->prepareData($validatedData, $nfoFile, true);
        $storedNfo = $data['nfo_file'] ?? null;

        try {
            return DB::transaction(function () use ($computerId, $data, $performer) {
                $computer = HardwareAsset::whereKey($computerId)->lockForUpdate()->firstOrFail();
                $before = $computer->getAttributes();

                if (! empty($data['nfo_file']) && $computer->nfo_file && Storage::disk('local')->exists($computer->nfo_file)) {
                    Storage::disk('local')->delete($computer->nfo_file);
                }

                $computer->update($data);
                $this->audit->record('update', 'hardware_assets', $computer->id, $before, $computer->fresh()->getAttributes(), $performer);

                return $computer->fresh();
            });
        } catch (Throwable $exception) {
            if ($storedNfo && str_starts_with($storedNfo, 'nfo/')) {
                Storage::disk('local')->delete($storedNfo);
            }
            throw $exception;
        }
    }

    /**
     * Prepare data array from validated request and optionally parse uploaded NFO file.
     */
    public function prepareData(array $validatedData, ?UploadedFile $nfoFile = null, bool $isUpdate = false): array
    {
        $data = $validatedData;

        foreach (self::BOOLEAN_FIELDS as $field) {
            $data[$field] = ! empty($validatedData[$field]);
        }

        if ($isUpdate && blank($data['admin_password'] ?? null)) {
            unset($data['admin_password']);
        }

        if ($nfoFile && $nfoFile->isValid()) {
            $parsed = $this->parser->parseFile($nfoFile->getRealPath());
            $nfoPath = $nfoFile->store('nfo', 'local');
            $data['nfo_file'] = $nfoPath;

            foreach ($parsed as $key => $value) {
                if (blank($data[$key] ?? null) && filled($value)) {
                    $data[$key] = $value;
                }
            }

            if (blank($data['name'] ?? null)) {
                $data['name'] = $nfoFile->getClientOriginalName();
            }
        }

        return $data;
    }
}