<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\InventoryDataset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class BulkActionController extends Controller
{
    public function index(Request $request, string $dataset, InventoryDataset $datasets): View
    {
        abort_unless(in_array($request->user()->role, ['admin','soporte'], true), 403);
        $set = $datasets->get($dataset);
        $search = trim((string) $request->query('search'));
        $query = $datasets->query($dataset);
        if ($search !== '') $query->where($set['display'], 'like', '%'.$search.'%');
        $records = $query->select(array_values(array_unique(array_filter([
            'id', $set['display'], isset($set['columns']['status']) ? 'status' : null,
            $set['bulk']['location'] ?? null,
        ]))))->paginate(50)->withQueryString();
        return view('bulk.index', compact('set','records','search'));
    }

    public function update(Request $request, string $dataset, InventoryDataset $datasets, AuditLogger $audit): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['admin','soporte'], true), 403);
        $set = $datasets->get($dataset);
        $allowed = array_filter([
            isset($set['bulk']['status']) ? 'status' : null,
            isset($set['bulk']['location']) ? 'location' : null,
            $request->user()->role === 'admin' ? 'delete' : null,
        ]);
        $data = $request->validate([
            'ids'=>['required','array','min:1','max:500'], 'ids.*'=>['integer'],
            'action'=>['required',Rule::in($allowed)], 'value'=>['nullable','string','max:150'],
        ]);
        if ($data['action'] === 'status') {
            validator($data, ['value'=>['required',Rule::in($set['bulk']['status'])]])->validate();
        } elseif ($data['action'] === 'location') {
            validator($data, ['value'=>['required','string','max:150']])->validate();
        }

        $records = DB::table($set['table'])->whereIn('id', $data['ids'])->get();
        try {
            DB::transaction(function () use ($records, $data, $set, $audit) {
                foreach ($records as $record) {
                    $before = (array) $record;
                    if ($data['action'] === 'delete') {
                        DB::table($set['table'])->where('id', $record->id)->delete();
                        $audit->record('bulk_delete', $set['table'], $record->id, $before, null);
                        continue;
                    }
                    $column = $data['action'] === 'status' ? 'status' : $set['bulk']['location'];
                    DB::table($set['table'])->where('id', $record->id)->update([$column=>$data['value'],'updated_at'=>now()]);
                    $audit->record('bulk_update', $set['table'], $record->id, $before, [$column=>$data['value']]);
                }
            });
        } catch (Throwable $exception) {
            report($exception);
            return back()->withInput()->with('error', 'No se pudo completar la accion. Hay registros relacionados que deben conservarse.');
        }
        return back()->with('success', $records->count().' registros actualizados correctamente.');
    }
}
