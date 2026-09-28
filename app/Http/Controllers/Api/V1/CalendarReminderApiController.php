<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CalendarReminder;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarReminderApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CalendarReminder::class);

        $requestedMonth = trim((string) $request->query('month'));
        $month = preg_match('/^\d{4}-\d{2}$/', $requestedMonth)
            ? $requestedMonth
            : now()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $items = CalendarReminder::query()
            ->whereBetween('event_date', [$start, $start->copy()->endOfMonth()])
            ->orderBy('event_date')
            ->orderBy('id')
            ->get()
            ->map(fn (CalendarReminder $item): array => $this->resource($item))
            ->values();

        $upcoming = CalendarReminder::query()
            ->whereDate('event_date', '>=', today())
            ->orderBy('event_date')
            ->orderBy('id')
            ->limit(10)
            ->get()
            ->map(fn (CalendarReminder $item): array => $this->resource($item))
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Agenda obtenida correctamente.',
            'data' => $items,
            'meta' => [
                'month' => $start->format('Y-m'),
                'total' => $items->count(),
                'upcoming' => $upcoming,
            ],
        ]);
    }

    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        $this->authorize('create', CalendarReminder::class);

        $item = CalendarReminder::create($this->validated($request));
        $audit->record('create', 'calendar_reminders', $item->id, null, $item->getAttributes());

        return response()->json([
            'success' => true,
            'message' => 'Recordatorio creado correctamente.',
            'data' => $this->resource($item),
        ], 201);
    }

    public function show(CalendarReminder $calendarReminder): JsonResponse
    {
        $this->authorize('view', $calendarReminder);

        return response()->json([
            'success' => true,
            'message' => 'Recordatorio obtenido correctamente.',
            'data' => $this->resource($calendarReminder),
        ]);
    }

    public function update(
        Request $request,
        CalendarReminder $calendarReminder,
        AuditLogger $audit,
    ): JsonResponse {
        $this->authorize('update', $calendarReminder);

        $before = $calendarReminder->getAttributes();
        $calendarReminder->update($this->validated($request));
        $fresh = $calendarReminder->fresh();
        $audit->record('update', 'calendar_reminders', $fresh->id, $before, $fresh->getAttributes());

        return response()->json([
            'success' => true,
            'message' => 'Recordatorio actualizado correctamente.',
            'data' => $this->resource($fresh),
        ]);
    }

    public function destroy(
        CalendarReminder $calendarReminder,
        AuditLogger $audit,
    ): JsonResponse {
        $this->authorize('delete', $calendarReminder);

        $before = $calendarReminder->getAttributes();
        $id = $calendarReminder->id;
        $calendarReminder->delete();
        $audit->record('delete', 'calendar_reminders', $id, $before, null);

        return response()->json([
            'success' => true,
            'message' => 'Recordatorio eliminado correctamente.',
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $request->merge(['is_done' => $request->boolean('is_done')]);

        return $request->validate([
            'event_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string'],
            'comments' => ['nullable', 'string'],
            'is_done' => ['boolean'],
        ]);
    }

    /** @return array<string, mixed> */
    private function resource(CalendarReminder $item): array
    {
        return [
            'id' => $item->id,
            'event_date' => $item->event_date?->format('Y-m-d'),
            'title' => $item->title,
            'color' => $item->color ?: '#E31B23',
            'description' => $item->description,
            'comments' => $item->comments,
            'is_done' => (bool) $item->is_done,
            'created_at' => $item->created_at?->toIso8601String(),
            'updated_at' => $item->updated_at?->toIso8601String(),
        ];
    }
}
