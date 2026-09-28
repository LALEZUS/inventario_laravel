<?php

namespace App\Http\Controllers;

use App\Models\Ink;
use App\Models\Toner;
use App\Support\RecentRecords;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintSupplyController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('viewAny', Ink::class);
        $this->authorize('viewAny', Toner::class);
        $type = $request->query('type') === 'toner' ? 'toner' : 'inks';
        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));

        $items = $type === 'inks'
            ? $this->inks($request, $search, $status)
            : $this->toners($request, $search, $status);

        return view('supplies.index', [
            'items' => $items,
            'type' => $type,
            'search' => $search,
            'status' => $status,
            'stats' => [
                'ink_types' => Ink::count(),
                'ink_units' => (int) Ink::sum('quantity'),
                'toner_types' => Toner::count(),
                'toner_units' => (int) Toner::sum('quantity'),
                'low_stock' => Ink::whereColumn('quantity', '<=', 'low_stock_threshold')->count()
                    + Toner::whereColumn('quantity', '<=', 'low_stock_threshold')->count(),
            ],
        ]);
    }

    private function inks(Request $request, string $search, string $status)
    {
        $query = Ink::query()
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(fn ($query) => $query->where('brand', 'like', $term)
                    ->orWhere('model', 'like', $term)->orWhere('color', 'like', $term)
                    ->orWhere('type', 'like', $term)->orWhere('capacity', 'like', $term)
                    ->orWhere('comments', 'like', $term));
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status));

        return RecentRecords::apply($query, $request, fn ($query) => $query
            ->orderBy('brand')->orderBy('type')->orderBy('color'))
            ->paginate(20)->withQueryString();
    }

    private function toners(Request $request, string $search, string $status)
    {
        $query = Toner::query()
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(fn ($query) => $query->where('brand', 'like', $term)
                    ->orWhere('model', 'like', $term)->orWhere('comentarios', 'like', $term)
                    ->orWhere('comments', 'like', $term));
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status));

        return RecentRecords::apply($query, $request, fn ($query) => $query->orderBy('brand')->orderBy('model'))
            ->paginate(20)->withQueryString();
    }
}
