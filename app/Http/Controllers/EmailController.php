<?php

namespace App\Http\Controllers;

use App\Models\EmailBackup;
use App\Models\MicrosoftEmail;
use App\Models\OfficeEmail;
use App\Support\RecentRecords;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('viewAny', MicrosoftEmail::class);
        $type = in_array($request->query('type'), ['microsoft', 'windows', 'backups'], true)
            ? $request->query('type')
            : 'microsoft';
        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));

        return view('emails.index', [
            'type' => $type,
            'search' => $search,
            'status' => $status,
            'statusOptions' => $this->statusOptions($type),
            'items' => match ($type) {
                'windows' => $this->accounts(OfficeEmail::query(), $request, $search, $status),
                'backups' => $this->backups($request, $search, $status),
                default => $this->accounts(MicrosoftEmail::query(), $request, $search, $status),
            },
            'stats' => [
                'microsoft' => MicrosoftEmail::count(),
                'windows' => OfficeEmail::count(),
                'backups' => EmailBackup::count(),
                'pending' => EmailBackup::where('is_done', false)->count(),
            ],
        ]);
    }

    private function accounts($query, Request $request, string $search, string $status)
    {
        $query->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
            ->where('email', 'like', '%'.$search.'%')
            ->orWhere('status', 'like', '%'.$search.'%')
            ->orWhere('comments', 'like', '%'.$search.'%')))
            ->when($status !== '', fn ($query) => $query->where('status', $status));

        return RecentRecords::apply($query, $request, fn ($query) => $query->orderBy('email'))
            ->paginate(25)->withQueryString();
    }

    private function backups(Request $request, string $search, string $status)
    {
        $query = EmailBackup::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('original_name', 'like', '%'.$search.'%')
                ->orWhere('original_email', 'like', '%'.$search.'%')
                ->orWhere('backup_name', 'like', '%'.$search.'%')
                ->orWhere('backup_email', 'like', '%'.$search.'%')
                ->orWhere('comments', 'like', '%'.$search.'%')))
            ->when($status === 'pending', fn ($query) => $query->where('is_done', false))
            ->when($status === 'done', fn ($query) => $query->where('is_done', true))
            ->when($status === 'archived', fn ($query) => $query->where('is_archived', true));

        return RecentRecords::apply($query, $request, fn ($query) => $query->latest('start_date'))
            ->paginate(25)->withQueryString();
    }

    private function statusOptions(string $type): array
    {
        if ($type === 'backups') {
            return ['pending', 'done', 'archived'];
        }

        $values = $type === 'windows'
            ? OfficeEmail::query()->pluck('status')
            : MicrosoftEmail::query()->pluck('status');

        return collect(config('inventory.catalogs.email_statuses'))->merge($values)->filter()->unique()->values()->all();
    }
}
