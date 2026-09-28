<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GlobalSearchController extends Controller
{
    public function index(Request $request, GlobalSearch $search): View
    {
        $query = trim((string) $request->query('q'));
        $results = mb_strlen($query) >= 2
            ? $search->search($query, 500, balanceTypes: false)
            : collect();
        $typeCounts = $results->countBy('type')->sortKeys();

        return view('search.index', compact('query', 'results', 'typeCounts'));
    }

    public function suggestions(Request $request, GlobalSearch $search): JsonResponse
    {
        $validated = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        return response()->json(['results' => $search->search($validated['q'], 10)])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}
