<?php

namespace App\Http\Controllers;

use App\Models\AccountCredential;
use App\Models\OutlookAccount;
use App\Models\SoftwareLicense;
use App\Support\RecentRecords;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CredentialController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('viewAny', AccountCredential::class);
        $type = in_array($request->query('type'), ['accounts', 'outlook', 'licenses'], true)
            ? $request->query('type')
            : 'accounts';
        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));

        return view('credentials.index', [
            'type' => $type,
            'search' => $search,
            'status' => $status,
            'statusOptions' => $this->statusOptions($type),
            'items' => match ($type) {
                'outlook' => $this->outlook($request, $search, $status),
                'licenses' => $this->licenses($request, $search, $status),
                default => $this->accounts($request, $search, $status),
            },
            'stats' => [
                'accounts' => AccountCredential::count(),
                'outlook' => OutlookAccount::count(),
                'licenses' => SoftwareLicense::count(),
                'expiring' => SoftwareLicense::whereNotNull('expiration_date')
                    ->whereBetween('expiration_date', [today(), today()->addDays(30)])->count(),
            ],
        ]);
    }

    private function accounts(Request $request, string $search, string $status)
    {
        $query = AccountCredential::with('employee')->when($search !== '', function ($query) use ($search) {
            $term = '%'.$search.'%';
            $query->where(fn ($query) => $query->where('email', 'like', $term)
                ->orWhere('account_type', 'like', $term)->orWhere('assigned_to', 'like', $term)
                ->orWhere('comments', 'like', $term));
        })->when($status !== '', fn ($query) => $query->where('status', $status));

        return RecentRecords::apply($query, $request, fn ($query) => $query->orderBy('email'))
            ->paginate(25)->withQueryString();
    }

    private function outlook(Request $request, string $search, string $status)
    {
        $query = OutlookAccount::with('employee')->when($search !== '', function ($query) use ($search) {
            $term = '%'.$search.'%';
            $query->where(fn ($query) => $query->where('correo', 'like', $term)
                ->orWhere('estatus', 'like', $term)->orWhere('comentarios', 'like', $term)
                ->orWhere('servidor_entrada', 'like', $term)->orWhere('servidor_salida', 'like', $term)
                ->orWhereHas('employee', fn ($employee) => $employee->where('full_name', 'like', $term)));
        })->when($status !== '', fn ($query) => $query->where('estatus', $status));

        return RecentRecords::apply($query, $request, fn ($query) => $query->orderBy('correo'))
            ->paginate(25)->withQueryString();
    }

    private function licenses(Request $request, string $search, string $status)
    {
        $query = SoftwareLicense::query()->when($search !== '', function ($query) use ($search) {
            $term = '%'.$search.'%';
            $query->where(fn ($query) => $query->where('name', 'like', $term)
                ->orWhere('type', 'like', $term)->orWhere('vendor', 'like', $term)
                ->orWhere('status', 'like', $term)->orWhere('comments', 'like', $term)
                ->orWhere('link', 'like', $term));
        })->when($status !== '', fn ($query) => $query->where('status', $status));

        return RecentRecords::apply($query, $request, fn ($query) => $query
            ->orderByRaw('expiration_date IS NULL, expiration_date')->orderBy('name'))
            ->paginate(25)->withQueryString();
    }

    private function statusOptions(string $type): array
    {
        [$defaults, $values] = match ($type) {
            'outlook' => [config('inventory.catalogs.email_statuses'), OutlookAccount::query()->pluck('estatus')],
            'licenses' => [config('inventory.catalogs.license_statuses'), SoftwareLicense::query()->pluck('status')],
            default => [config('inventory.catalogs.account_statuses'), AccountCredential::query()->pluck('status')],
        };

        return collect($defaults)->merge($values)->filter()->unique()->values()->all();
    }
}
