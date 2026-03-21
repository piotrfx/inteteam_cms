<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CmsNavigation;

final class NavigationService
{
    /**
     * Get navigation for a location, or null if none saved.
     *
     * @return list<array{label: string, url: string, target?: string}>|null
     */
    public function get(string $companyId, string $location): ?array
    {
        $nav = CmsNavigation::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('location', $location)
            ->first();

        return $nav?->items;
    }

    /**
     * Save (upsert) navigation items for a location.
     *
     * @param  list<array{label: string, url: string, target?: string}>  $items
     */
    public function save(string $companyId, string $location, array $items): CmsNavigation
    {
        return CmsNavigation::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $companyId, 'location' => $location],
            ['items' => $items],
        );
    }
}
