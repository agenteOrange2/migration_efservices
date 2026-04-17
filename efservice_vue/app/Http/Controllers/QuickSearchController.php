<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuickSearchController extends Controller
{
    public function __invoke(Request $request, SearchService $searchService): JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 403);

        $query = trim((string) $request->string('q'));
        $results = $searchService->search($query, $user);

        return response()->json([
            'query' => $query,
            'navigation' => $results['navigation'] ?? [],
            'entities' => $results['entities'] ?? [],
            'quickActions' => $searchService->getQuickActions($user),
        ]);
    }
}
