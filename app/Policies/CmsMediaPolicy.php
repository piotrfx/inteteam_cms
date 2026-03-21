<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CmsMedia;
use App\Models\CmsUser;

final class CmsMediaPolicy
{
    public function viewAny(CmsUser $user): bool
    {
        return true;
    }

    public function view(CmsUser $user, CmsMedia $media): bool
    {
        return $user->company_id === $media->company_id;
    }

    public function create(CmsUser $user): bool
    {
        return in_array($user->role, ['admin', 'editor'], true);
    }

    public function update(CmsUser $user, CmsMedia $media): bool
    {
        return $user->company_id === $media->company_id
            && in_array($user->role, ['admin', 'editor'], true);
    }

    public function delete(CmsUser $user, CmsMedia $media): bool
    {
        return $user->company_id === $media->company_id
            && $user->role === 'admin';
    }
}
