<?php

namespace App\Services;

use App\Main\SideMenu;
use App\Main\CarrierSideMenu;
use App\Main\DriverSideMenu;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

/**
 * Navigation Provider Service
 * 
 * Provides role-based navigation items from existing SideMenu classes.
 * Converts nested menu structures to flat searchable items.
 */
class NavigationProvider
{
    /**
     * Get flattened navigation items for a user role
     *
     * @param string $role User role (superadmin, user_carrier, user_driver)
     * @return array Flattened navigation items with routes
     */
    public function getItemsForRole(string $role): array
    {
        try {
            $menu = $this->getMenuForRole($role);
            
            if (empty($menu)) {
                return [];
            }
            
            return $this->flattenMenu($menu);
        } catch (\Exception $e) {
            Log::error("NavigationProvider: Error getting items for role", [
                'role' => $role,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get the appropriate menu array based on user role
     *
     * @param string $role
     * @return array
     */
    protected function getMenuForRole(string $role): array
    {
        return match ($role) {
            'superadmin' => SideMenu::menu(),
            'user_carrier' => CarrierSideMenu::menu(),
            'user_driver' => DriverSideMenu::menu(),
            default => []
        };
    }

    /**
     * Convert nested menu structure to flat searchable items
     *
     * @param array $menu
     * @param string $parentTitle
     * @param string $parentIcon
     * @param string $inheritedSection
     * @return array
     */
    protected function flattenMenu(array $menu, string $parentTitle = '', string $parentIcon = '', string $inheritedSection = ''): array
    {
        $items = [];
        $currentSection = $inheritedSection;

        foreach ($menu as $menuItem) {
            try {
                // Handle section dividers (strings)
                if (is_string($menuItem)) {
                    $currentSection = $menuItem;
                    continue;
                }

                // Skip if not an array
                if (!is_array($menuItem)) {
                    continue;
                }

                // Skip items without route or with placeholder routes
                if (!isset($menuItem['route_name']) || $menuItem['route_name'] === '#' || empty($menuItem['route_name'])) {
                    // But process sub_menu if exists
                    if (isset($menuItem['sub_menu']) && is_array($menuItem['sub_menu'])) {
                        $subItems = $this->flattenMenu(
                            $menuItem['sub_menu'],
                            $menuItem['title'] ?? '',
                            $menuItem['icon'] ?? '',
                            $currentSection
                        );
                        $items = array_merge($items, $subItems);
                    }
                    continue;
                }

                // Build the navigation item
                $item = $this->buildNavigationItem($menuItem, $parentTitle, $currentSection);
                
                if ($item !== null) {
                    $items[] = $item;
                }

                // Process sub_menu recursively
                if (isset($menuItem['sub_menu']) && is_array($menuItem['sub_menu'])) {
                    $subItems = $this->flattenMenu(
                        $menuItem['sub_menu'],
                        $menuItem['title'] ?? '',
                        $menuItem['icon'] ?? '',
                        $currentSection
                    );
                    $items = array_merge($items, $subItems);
                }
            } catch (\Exception $e) {
                Log::warning("NavigationProvider: Error processing menu item", [
                    'error' => $e->getMessage(),
                    'menuItem' => $menuItem
                ]);
                continue;
            }
        }

        return $items;
    }

    /**
     * Build a navigation item from menu data
     *
     * @param array $menuItem
     * @param string $parentTitle
     * @param string $section
     * @return array|null
     */
    protected function buildNavigationItem(array $menuItem, string $parentTitle, string $section): ?array
    {
        $routeName = $menuItem['route_name'];
        $params = $menuItem['params'] ?? [];

        // Check if route exists
        if (!Route::has($routeName)) {
            Log::debug("NavigationProvider: Route not found - {$routeName}");
            return null;
        }

        try {
            $url = route($routeName, $params);
        } catch (\Exception $e) {
            Log::debug("NavigationProvider: Could not generate URL for route - {$routeName}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }

        $title = $menuItem['title'] ?? '';
        $fullPath = $parentTitle ? "{$parentTitle} > {$title}" : $title;

        return [
            'id' => 'nav_' . md5($routeName . $title),
            'type' => 'navigation',
            'title' => $title,
            'fullPath' => $fullPath,
            'section' => $section,
            'icon' => $menuItem['icon'] ?? 'Circle',
            'route' => $routeName,
            'url' => $url,
            'keywords' => $this->generateKeywords($title, $fullPath, $section),
        ];
    }

    /**
     * Generate search keywords for a navigation item
     *
     * @param string $title
     * @param string $fullPath
     * @param string $section
     * @return array
     */
    protected function generateKeywords(string $title, string $fullPath, string $section): array
    {
        $keywords = [];
        
        // Add title words
        $keywords = array_merge($keywords, explode(' ', strtolower($title)));
        
        // Add section words
        if ($section) {
            $keywords = array_merge($keywords, explode(' ', strtolower($section)));
        }
        
        // Add full path words
        $pathParts = explode(' > ', $fullPath);
        foreach ($pathParts as $part) {
            $keywords = array_merge($keywords, explode(' ', strtolower($part)));
        }

        // Remove duplicates and empty strings
        return array_values(array_unique(array_filter($keywords)));
    }

    /**
     * Get all available roles
     *
     * @return array
     */
    public function getAvailableRoles(): array
    {
        return ['superadmin', 'user_carrier', 'user_driver'];
    }
}
