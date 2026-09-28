<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\GlobalSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchApiController extends Controller
{
    public function search(Request $request, GlobalSearch $searchService): JsonResponse
    {
        $term = trim((string) $request->query('q', $request->query('search', '')));

        if (mb_strlen($term) < 2) {
            return response()->json([
                'success' => true,
                'message' => 'Resultados de búsqueda obtenidos.',
                'data' => [
                    'query' => $term,
                    'total_matches' => 0,
                    'results' => [
                        'computers' => [
                            'label' => 'Computadoras',
                            'count' => 0,
                            'items' => [],
                        ],
                        'cellphones' => [
                            'label' => 'Celulares',
                            'count' => 0,
                            'items' => [],
                        ],
                        'employees' => [
                            'label' => 'Empleados',
                            'count' => 0,
                            'items' => [],
                        ],
                    ],
                ],
            ], 200);
        }

        $results = $searchService->search($term);

        $compItems = $results->where('type', 'Computadora')->map(function ($item) {
            return [
                'id' => $item['id'] ?? null,
                'title' => $item['title'] ?? '',
                'subtitle' => $item['subtitle'] ?? '',
                'status' => $item['status'] ?? null,
            ];
        })->values()->all();

        $cellItems = $results->where('type', 'Celular')->map(function ($item) {
            return [
                'id' => $item['id'] ?? null,
                'title' => $item['title'] ?? '',
                'subtitle' => $item['subtitle'] ?? '',
                'status' => $item['status'] ?? null,
            ];
        })->values()->all();

        $empItems = $results->where('type', 'Empleado')->map(function ($item) {
            return [
                'id' => $item['id'] ?? null,
                'title' => $item['title'] ?? '',
                'subtitle' => $item['subtitle'] ?? '',
                'status' => $item['status'] ?? null,
            ];
        })->values()->all();

        $formattedResults = [
            'computers' => [
                'label' => 'Computadoras',
                'count' => count($compItems),
                'items' => $compItems,
            ],
            'cellphones' => [
                'label' => 'Celulares',
                'count' => count($cellItems),
                'items' => $cellItems,
            ],
            'employees' => [
                'label' => 'Empleados',
                'count' => count($empItems),
                'items' => $empItems,
            ],
        ];

        $totalMatches = count($compItems) + count($cellItems) + count($empItems);

        return response()->json([
            'success' => true,
            'message' => 'Resultados de búsqueda obtenidos.',
            'data' => [
                'query' => $term,
                'total_matches' => $totalMatches,
                'results' => $formattedResults,
            ],
        ], 200);
    }
}
