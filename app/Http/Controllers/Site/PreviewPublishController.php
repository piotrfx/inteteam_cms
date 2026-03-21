<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Services\PreviewTokenService;
use App\Services\RevisionService;
use Illuminate\Http\RedirectResponse;

final class PreviewPublishController extends Controller
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
            $this->revisionService->publishStaged($content);
            $url = url("/{$content->slug}");
        } else {
            $content = CmsPost::withoutGlobalScopes()->findOrFail($record->content_id);
            $this->revisionService->publishStaged($content);
            $url = url("/blog/{$content->slug}");
        }

        return redirect($url)->with(['alert' => 'Changes published successfully.', 'type' => 'success']);
    }
}
