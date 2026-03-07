<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\SearchService;
use Illuminate\Support\Facades\Auth;

/**
 * Quick Search Livewire Component
 * 
 * Provides real-time search functionality with role-based navigation
 * and entity search capabilities.
 */
class QuickSearch extends Component
{
    public string $query = '';
    public array $navigationItems = [];
    public array $filteredNavigation = [];
    public array $entityResults = [];
    public array $quickActions = [];
    public bool $isLoading = false;
    public bool $showResults = true;

    /**
     * Get SearchService instance
     */
    protected function getSearchService(): SearchService
    {
        return app(SearchService::class);
    }

    /**
     * Mount the component
     */
    public function mount(): void
    {
        try {
            $user = Auth::user();
            
            if ($user) {
                $searchService = $this->getSearchService();
                // Load navigation items on mount (for client-side filtering)
                $this->navigationItems = $searchService->getNavigationItems($user);
                $this->filteredNavigation = $this->navigationItems;
                $this->quickActions = $searchService->getQuickActions($user);
            }
        } catch (\Exception $e) {            
            $this->navigationItems = [];
            $this->filteredNavigation = [];
            $this->quickActions = [];
        }
    }

    /**
     * Handle query updates with debounce
     */
    public function updatedQuery(): void
    {
        $this->filterNavigation();
        
        // Only search entities if query is 3+ characters
        if (strlen(trim($this->query)) >= 3) {
            $this->searchEntities();
        } else {
            $this->entityResults = [];
        }
        
        // Dispatch event to refresh icons
        $this->dispatch('searchUpdated');
    }

    /**
     * Filter navigation items client-side (instant)
     */
    protected function filterNavigation(): void
    {
        if (empty($this->query)) {
            $this->filteredNavigation = $this->navigationItems;
            return;
        }

        $query = strtolower(trim($this->query));
        
        $this->filteredNavigation = array_values(array_filter(
            $this->navigationItems,
            function ($item) use ($query) {
                // Search in title
                if (stripos($item['title'] ?? '', $query) !== false) {
                    return true;
                }
                
                // Search in full path
                if (stripos($item['fullPath'] ?? '', $query) !== false) {
                    return true;
                }
                
                // Search in section
                if (stripos($item['section'] ?? '', $query) !== false) {
                    return true;
                }
                
                // Search in keywords
                if (isset($item['keywords']) && is_array($item['keywords'])) {
                    foreach ($item['keywords'] as $keyword) {
                        if (stripos($keyword, $query) !== false) {
                            return true;
                        }
                    }
                }
                
                return false;
            }
        ));
    }

    /**
     * Search entities via API (debounced)
     */
    public function searchEntities(): void
    {
        $user = Auth::user();
        
        
        if (!$user || strlen(trim($this->query)) < 3) {
            $this->entityResults = [];
            return;
        }

        $this->isLoading = true;

        try {
            $searchService = $this->getSearchService();
            $results = $searchService->search($this->query, $user);

            $this->entityResults = $results['entities'] ?? [];            
        } catch (\Exception $e) {            
            $this->entityResults = [];
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Clear search and reset results
     */
    public function clearSearch(): void
    {
        $this->query = '';
        $this->filteredNavigation = $this->navigationItems;
        $this->entityResults = [];
    }

    /**
     * Check if there are any results
     */
    public function hasResults(): bool
    {
        return !empty($this->filteredNavigation) || !empty($this->entityResults);
    }

    /**
     * Get grouped navigation items by section
     */
    public function getGroupedNavigation(): array
    {
        $grouped = [];
        
        foreach ($this->filteredNavigation as $item) {
            $section = $item['section'] ?? 'Other';
            if (!isset($grouped[$section])) {
                $grouped[$section] = [];
            }
            $grouped[$section][] = $item;
        }
        
        return $grouped;
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.quick-search', [
            'groupedNavigation' => $this->getGroupedNavigation(),
            'hasResults' => $this->hasResults(),
        ]);
    }
}
