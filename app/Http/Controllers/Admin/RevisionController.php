<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\CmsPageRevision;
use App\Models\CmsPost;
use App\Services\RevisionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class RevisionController extends Controller
{
    public function __construct(private readonly RevisionService $revisionService) {}

    public function pageIndex(CmsPage $page): Response
    {
        abort_unless(auth('cms')->user()?->company_id === $page->company_id, 403);

        $history = $this->revisionService->history($page);

        return Inertia::render('Admin/Revisions/Index', [
            'content' => ['id' => $page->id, 'title' => $page->title, 'type' => 'page'],
            'revisions' => $history->map(fn (CmsPageRevision $r) => [
                'id' => $r->id,
                'summary' => $r->summary,
                'created_by_type' => $r->created_by_type,
                'created_by_id' => $r->created_by_id,
                'created_at' => $r->created_at?->toISOString(),
                'is_live' => $r->id === $page->live_revision_id,
                'is_staged' => $r->id === $page->staged_revision_id,
            ]),
        ]);
    }

    public function postIndex(CmsPost $post): Response
    {
        abort_unless(auth('cms')->user()?->company_id === $post->company_id, 403);

        $history = $this->revisionService->history($post);

        return Inertia::render('Admin/Revisions/Index', [
            'content' => ['id' => $post->id, 'title' => $post->title, 'type' => 'post'],
            'revisions' => $history->map(fn (CmsPageRevision $r) => [
                'id' => $r->id,
                'summary' => $r->summary,
                'created_by_type' => $r->created_by_type,
                'created_by_id' => $r->created_by_id,
                'created_at' => $r->created_at?->toISOString(),
                'is_live' => $r->id === $post->live_revision_id,
                'is_staged' => $r->id === $post->staged_revision_id,
            ]),
        ]);
    }

    public function restorePage(CmsPage $page, CmsPageRevision $revision): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->role === 'admin', 403);
        abort_unless($page->company_id === $revision->company_id, 403);

        $this->revisionService->rollBackTo($page, $revision);

        return back()->with(['alert' => 'Revision restored as staged. Review and publish when ready.', 'type' => 'success']);
    }

    public function restorePost(CmsPost $post, CmsPageRevision $revision): RedirectResponse
    {
        abort_unless(auth('cms')->user()?->role === 'admin', 403);
        abort_unless($post->company_id === $revision->company_id, 403);

        $this->revisionService->rollBackTo($post, $revision);

        return back()->with(['alert' => 'Revision restored as staged. Review and publish when ready.', 'type' => 'success']);
    }
}
