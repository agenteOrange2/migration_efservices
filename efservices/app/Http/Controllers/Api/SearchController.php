<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Search API Controller
 * 
 * Provides unified search endpoint for Quick Search feature.
 * Returns role-based navigation items and entity search results.
 */
class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * Search across navigation and entities
     * 
     * GET /api/search?query={query}
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $query = $request->input('query', '');
        
        try {
            $results = $this->searchService->search($query, $user);
            
            return response()->json([
                'success' => true,
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get navigation items for current user
     * 
     * GET /api/search/navigation
     *
     * @return JsonResponse
     */
    public function navigation(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            $items = $this->searchService->getNavigationItems($user);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'navigation' => $items,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load navigation',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get quick actions for current user
     * 
     * GET /api/search/quick-actions
     *
     * @return JsonResponse
     */
    public function quickActions(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            $actions = $this->searchService->getQuickActions($user);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'quickActions' => $actions,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load quick actions',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
