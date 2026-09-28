<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryEventController extends Controller
{
    private const ENTITIES = [
        'hardware_assets', 'cellphones', 'peripherals', 'employees', 'printers',
        'inks', 'toner', 'asset_files', 'assignments', 'maintenance_logs',
        'network_devices', 'watchguard_users', 'enterprise_networks',
        'account_management', 'correos_outlook', 'licenses',
        'microsoft_emails', 'office_emails', 'email_backups',
        'tutorials', 'gallery', 'ftp_catalog',
        'notes', 'calendar_reminders', 'backup_runs',
    ];

    public function __invoke(Request $request): StreamedResponse
    {
        $since = max(0, (int) $request->query('since', 0), (int) $request->header('Last-Event-ID', 0));

        return response()->stream(function () use ($since) {
            @ini_set('zlib.output_compression', '0');
            @set_time_limit(0);
            $cursor = $since;
            $started = microtime(true);
            $lastKeepAlive = 0.0;

            echo ': '.str_repeat(' ', 2048)."\n";
            echo "retry: 2500\n\n";
            $this->flush();

            do {
                $events = AuditLog::query()
                    ->where('id', '>', $cursor)
                    ->whereIn('entity', self::ENTITIES)
                    ->orderBy('id')
                    ->limit(50)
                    ->get();

                foreach ($events as $event) {
                    $cursor = $event->id;
                    echo 'id: '.$event->id."\n";
                    echo "event: inventory-update\n";
                    echo 'data: '.json_encode($this->payload($event), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";
                }

                if ($events->isNotEmpty()) {
                    $this->flush();
                }

                if (app()->runningUnitTests() || PHP_SAPI === 'cli-server') {
                    break;
                }

                if (microtime(true) - $lastKeepAlive >= 10) {
                    echo ': keep-alive '.now()->timestamp."\n\n";
                    $lastKeepAlive = microtime(true);
                    $this->flush();
                }

                if (connection_aborted()) {
                    break;
                }

                usleep(900000);
            } while (microtime(true) - $started < 25);
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        $since = max(0, (int) $request->query('since', 0));
        $events = AuditLog::query()
            ->where('id', '>', $since)
            ->whereIn('entity', self::ENTITIES)
            ->orderBy('id')
            ->limit(50)
            ->get();

        return response()->json([
            'cursor' => (int) ($events->last()?->id ?? $since),
            'events' => $events->map(fn (AuditLog $event) => $this->payload($event))->values(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    private function payload(AuditLog $event): array
    {
        $after = is_array($event->after_data) ? $event->after_data : [];
        $before = is_array($event->before_data) ? $event->before_data : [];

        return [
            'id' => $event->id,
            'entity' => $event->entity,
            'entity_id' => $event->entity_id,
            'action' => $event->action,
            'asset_type' => $after['asset_type'] ?? $before['asset_type'] ?? null,
            'asset_id' => $after['asset_id'] ?? $before['asset_id'] ?? null,
            'actor' => $event->user_name,
            'occurred_at' => $event->created_at?->toIso8601String(),
        ];
    }

    private function flush(): void
    {
        if (ob_get_level() > 0) {
            @ob_flush();
        }
        flush();
    }
}
