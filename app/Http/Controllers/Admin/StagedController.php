<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Services\PreviewTokenService;
use App\Services\RevisionService;
use Illuminate\Http\RedirectResponse;

final class StagedController extends Controller
{
    public function __construct(
        private readonly RevisionService $revisionService,
        private readonly PreviewTokenService $previewTokenService,
    ) {}

    public function publishPage(CmsPage $page): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->role === 'admin', 403);
        abort_unless(auth('cms')->user()->company_id === $page->company_id, 403);

        $this->revisionService->publishStaged($page);

        return back()->with(['alert' => 'Staged changes published to live.', 'type' => 'success']);
    }

    public function discardPage(CmsPage $page): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->role === 'admin', 403);
        abort_unless(auth('cms')->user()->company_id === $page->company_id, 403);

        $this->revisionService->discardStaged($page);

        return back()->with(['alert' => 'Staged changes discarded.', 'type' => 'success']);
    }

    public function previewPage(CmsPage $page): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->company_id === $page->company_id, 403);
        abort_unless($page->staged_revision_id !== null, 422, 'No staged revision to preview.');

        $token = $this->previewTokenService->createForPage($page, 'user');

        return redirect("/preview/{$token->token}");
    }

    public function publishPost(CmsPost $post): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->role === 'admin', 403);
        abort_unless(auth('cms')->user()->company_id === $post->company_id, 403);

        $this->revisionService->publishStaged($post);

        return back()->with(['alert' => 'Staged changes published to live.', 'type' => 'success']);
    }

    public function discardPost(CmsPost $post): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->role === 'admin', 403);
        abort_unless(auth('cms')->user()->company_id === $post->company_id, 403);

        $this->revisionService->discardStaged($post);

        return back()->with(['alert' => 'Staged changes discarded.', 'type' => 'success']);
    }

    public function previewPost(CmsPost $post): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->company_id === $post->company_id, 403);
        abort_unless($post->staged_revision_id !== null, 422, 'No staged revision to preview.');

        $token = $this->previewTokenService->createForPost($post, 'user');

        return redirect("/preview/{$token->token}");
    }
}
