<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CmsPage;
use App\Models\CmsUser;

final class CmsPagePolicy
{
    public function viewAny(CmsUser $user): bool
    {
        return true;
    }

    public function view(CmsUser $user, CmsPage $page): bool
    {
        return $user->company_id === $page->company_id;
    }

    public function create(CmsUser $user): bool
    {
        return in_array($user->role, ['admin', 'editor'], true);
    }

    public function update(CmsUser $user, CmsPage $page): bool
    {
        return $user->company_id === $page->company_id
            && in_array($user->role, ['admin', 'editor'], true);
    }

    public function publish(CmsUser $user, CmsPage $page): bool
    {
        return $user->company_id === $page->company_id
            && $user->role === 'admin';
    }

    public function delete(CmsUser $user, CmsPage $page): bool
    {
        return $user->company_id === $page->company_id
            && $user->role === 'admin';
    }
}
