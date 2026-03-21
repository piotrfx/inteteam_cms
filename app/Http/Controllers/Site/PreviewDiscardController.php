<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Services\PreviewTokenService;
use App\Services\RevisionService;
use Illuminate\Http\RedirectResponse;

final class PreviewDiscardController extends Controller
{
    public function __construct(
        private readonly PreviewTokenService $previewTokenService,
        private readonly RevisionService $revisionService,
    ) {}

    public function __invoke(string $token): RedirectResponse
    {
        $record = $this->previewTokenService->validate($token);

        if ($record->content_type === 'page') {
            $content = CmsPage::withoutGlobalScopes()->findOrFail($record->content_id);
            $this->revisionService->discardStaged($content);
            $url = url('/admin/pages/' . $content->id . '/edit');
        } else {
            $content = CmsPost::withoutGlobalScopes()->findOrFail($record->content_id);
            $this->revisionService->discardStaged($content);
            $url = url('/admin/posts/' . $content->id . '/edit');
        }

        return redirect($url)->with(['alert' => 'Staged changes discarded.', 'type' => 'success']);
    }
}
