<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CmsNavigation;

final class NavigationService
{
    /**
     * Get navigation for a location, or null if none saved.
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
     */
    public function save(string $companyId, string $location, array $items): CmsNavigation
    {
        return CmsNavigation::withoutGlobalScopes()->updateOrCreate(
            ['company_id' => $companyId, 'location' => $location],
            ['items' => $items],
        );
    }
}
