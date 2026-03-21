<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CmsPost;
use App\Models\CmsUser;

final class CmsPostPolicy
{
    public function viewAny(CmsUser $user): bool
    {
        return true;
    }

    public function view(CmsUser $user, CmsPost $post): bool
    {
        return $user->company_id === $post->company_id;
    }

    public function create(CmsUser $user): bool
    {
        return in_array($user->role, ['admin', 'editor'], true);
    }

    public function update(CmsUser $user, CmsPost $post): bool
    {
        return $user->company_id === $post->company_id
            && in_array($user->role, ['admin', 'editor'], true);
    }

    public function publish(CmsUser $user, CmsPost $post): bool
    {
        return $user->company_id === $post->company_id
            && $user->role === 'admin';
    }

    public function delete(CmsUser $user, CmsPost $post): bool
    {
        return $user->company_id === $post->company_id
            && $user->role === 'admin';
    }
}
