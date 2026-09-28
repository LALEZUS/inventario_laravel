<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\NoteRequest;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoteApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Note::class);

        $query = Note::query();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $items = \App\Support\RecentRecords::apply(
            $query,
            $request,
            fn ($query) => $query->latest()
        )->paginate(20);

        return response()->json([
            'success' => true,
            'data' => NoteResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        return response()->json([
            'success' => true,
            'data' => new NoteResource($note, true),
        ]);
    }

    public function store(NoteRequest $request, AuditLogger $logger): JsonResponse
    {
        $this->authorize('create', Note::class);

        $data = $request->validated();

        $note = DB::transaction(function () use ($data, $logger) {
            $m = Note::create($data);
            $logger->record('create', 'notes', $m->id, null, $m->getAttributes());
            return $m;
        });

        return response()->json([
            'success' => true,
            'message' => 'Nota registrada correctamente.',
            'data' => new NoteResource($note, true),
        ], 201);
    }

    public function update(NoteRequest $request, Note $note, AuditLogger $logger): JsonResponse
    {
        $this->authorize('update', $note);

        $before = $note->getAttributes();
        $data = $request->validated();

        DB::transaction(function () use ($note, $data, $before, $logger) {
            $note->update($data);
            $logger->record('update', 'notes', $note->id, $before, $note->fresh()->getAttributes());
        });

        return response()->json([
            'success' => true,
            'message' => 'Nota actualizada correctamente.',
            'data' => new NoteResource($note->fresh(), true),
        ]);
    }

    public function destroy(Note $note, AuditLogger $logger): JsonResponse
    {
        $this->authorize('delete', $note);

        $before = $note->getAttributes();
        $note->delete();

        $logger->record('delete', 'notes', $note->id, $before, null);

        return response()->json([
            'success' => true,
            'message' => 'Nota eliminada correctamente.',
        ]);
    }
}
