<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\EnterpriseNetwork;
use App\Models\NetworkDevice;
use App\Models\WatchguardUser;
use App\Support\RecentRecords;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NetworkController extends Controller
{
    public function acceptableUse(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('viewAny', NetworkDevice::class);

        $letterhead = public_path('images/MembretadaTG2026.png');
        $withLetterhead = $request->boolean('with_letterhead', true);
        $letterheadData = $withLetterhead && is_file($letterhead)
            ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($letterhead))
            : null;

        $pdf = Pdf::loadView('network.acceptable-use-pdf', [
            'letterheadData' => $letterheadData,
            'issuedAt' => now()->format('d/m/Y'),
        ])->setPaper('letter');

        $filename = $withLetterhead
            ? 'aviso-uso-adecuado-de-la-red-total-ground.pdf'
            : 'aviso-uso-adecuado-de-la-red-total-ground-sin-membrete.pdf';

        return $request->boolean('preview')
            ? $pdf->stream($filename)
            : $pdf->download($filename);
    }

    public function __invoke(Request $request): View
    {
        $this->authorize('viewAny', NetworkDevice::class);
        $type = in_array($request->query('type'), ['devices', 'watchguard', 'networks'], true) ? $request->query('type') : 'devices';
        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));

        return view('network.index', [
            'type' => $type,
            'search' => $search,
            'status' => $status,
            'items' => match ($type) {
                'watchguard' => $this->watchguard($request, $search),
                'networks' => $this->networks($request, $search),
                default => $this->devices($request, $search, $status),
            },
            'stats' => [
                'devices' => NetworkDevice::count(),
                'active' => NetworkDevice::where('status', 'Activo')->count(),
                'watchguard' => WatchguardUser::count(),
                'networks' => EnterpriseNetwork::count(),
            ],
        ]);
    }

    private function devices(Request $request, string $search, string $status)
    {
        $query = NetworkDevice::query()->when($search !== '', function ($query) use ($search) {
            $term = '%'.$search.'%';
            $query->where(fn ($query) => $query->where('device_name', 'like', $term)->orWhere('device_type', 'like', $term)
                ->orWhere('ip_address', 'like', $term)->orWhere('mac_address', 'like', $term)
                ->orWhere('location', 'like', $term)->orWhere('brand', 'like', $term));
        })->when($status !== '', fn ($query) => $query->where('status', $status));

        return RecentRecords::apply($query, $request, fn ($query) => $query->orderBy('device_name'))
            ->paginate(25)->withQueryString();
    }

    private function watchguard(Request $request, string $search)
    {
        $query = WatchguardUser::query()->when($search !== '', function ($query) use ($search) {
            $term = '%'.$search.'%';
            $query->where(fn ($query) => $query->where('username', 'like', $term)->orWhere('assigned_to', 'like', $term)
                ->orWhere('area', 'like', $term)->orWhere('ip', 'like', $term));
        });

        return RecentRecords::apply($query, $request, fn ($query) => $query->orderBy('username'))
            ->paginate(25)->withQueryString();
    }

    private function networks(Request $request, string $search)
    {
        $query = EnterpriseNetwork::query()->when($search !== '', function ($query) use ($search) {
            $term = '%'.$search.'%';
            $query->where(fn ($query) => $query->where('network_name', 'like', $term)->orWhere('vlan', 'like', $term)
                ->orWhere('location', 'like', $term)->orWhere('encryption', 'like', $term)->orWhere('comments', 'like', $term));
        });

        return RecentRecords::apply($query, $request, fn ($query) => $query->orderBy('network_name'))
            ->paginate(25)->withQueryString();
    }
}
