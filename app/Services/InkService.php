<?php

namespace App\Services;

use App\Models\Ink;
use App\Models\Printer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InkService
{
    public function __construct(
        protected AuditLogger $audit
    ) {}

    public function store(array $data, ?User $user = null): Ink
    {
        return DB::transaction(function () use ($data) {
            $ink = Ink::create($data);
            $this->audit->record('create', 'inks', $ink->id, null, $ink->getAttributes());

            return $ink;
        });
    }

    public function update(Ink $ink, array $data, ?User $user = null): Ink
    {
        $before = $ink->getAttributes();

        return DB::transaction(function () use ($ink, $data, $before) {
            $ink->update($data);
            $fresh = $ink->fresh();
            $this->audit->record('update', 'inks', $ink->id, $before, $fresh->getAttributes());

            return $fresh;
        });
    }

    public function delete(Ink $ink, ?User $user = null): void
    {
        $before = $ink->getAttributes();

        DB::transaction(function () use ($ink, $before) {
            Printer::all()->each(function (Printer $printer) use ($ink) {
                $ids = array_values(array_filter($printer->linkedInkIds(), fn (int $id) => $id !== $ink->id));
                if ($ids !== $printer->linkedInkIds()) {
                    $printer->update(['linked_inks' => $ids]);
                }
            });

            $ink->delete();
            $this->audit->record('delete', 'inks', $ink->id, $before, null);
        });
    }
}