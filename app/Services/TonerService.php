<?php

namespace App\Services;

use App\Models\Printer;
use App\Models\Toner;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TonerService
{
    public function __construct(
        protected AuditLogger $audit
    ) {}

    public function store(array $data, ?User $user = null): Toner
    {
        $data = $this->prepareData($data);

        return DB::transaction(function () use ($data) {
            $toner = Toner::create($data);
            $this->audit->record('create', 'toner', $toner->id, null, $toner->getAttributes());

            return $toner;
        });
    }

    public function update(Toner $toner, array $data, ?User $user = null): Toner
    {
        $before = $toner->getAttributes();
        $data = $this->prepareData($data);

        return DB::transaction(function () use ($toner, $data, $before) {
            $toner->update($data);
            $fresh = $toner->fresh();
            $this->audit->record('update', 'toner', $toner->id, $before, $fresh->getAttributes());

            return $fresh;
        });
    }

    public function delete(Toner $toner, ?User $user = null): void
    {
        $before = $toner->getAttributes();

        DB::transaction(function () use ($toner, $before) {
            Printer::all()->each(function (Printer $printer) use ($toner) {
                $ids = array_values(array_filter($printer->linkedTonerIds(), fn (int $id) => $id !== $toner->id));
                if ($ids !== $printer->linkedTonerIds()) {
                    $printer->update(['linked_toner' => $ids]);
                }
            });

            $toner->delete();
            $this->audit->record('delete', 'toner', $toner->id, $before, null);
        });
    }

    public function prepareData(array $data): array
    {
        if (array_key_exists('comments', $data)) {
            $data['comentarios'] = $data['comments'];
        }

        return $data;
    }
}