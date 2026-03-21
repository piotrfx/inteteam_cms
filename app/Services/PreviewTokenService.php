<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CmsPage;
use App\Models\CmsPageRevision;
use App\Models\CmsPost;
use App\Models\CmsPreviewToken;
use Illuminate\Support\Str;
use RuntimeException;

final class PreviewTokenService
{
    public function createForPage(CmsPage $page, string $createdByType = 'user'): CmsPreviewToken
    {
        $revision = $this->requireStagedRevision($page);

        return $this->make(
            companyId: $page->company_id,
            contentType: 'page',
            contentId: $page->id,
            revisionId: $revision->id,
            createdByType: $createdByType,
        );
    }

    public function createForPost(CmsPost $post, string $createdByType = 'user'): CmsPreviewToken
    {
        $revision = $this->requireStagedRevision($post);

        return $this->make(
            companyId: $post->company_id,
            contentType: 'post',
            contentId: $post->id,
            revisionId: $revision->id,
            createdByType: $createdByType,
        );
    }

    public function validate(string $token): CmsPreviewToken
    {
        $record = CmsPreviewToken::withoutGlobalScopes()
            ->where('token', $token)
            ->first();

        if ($record === null || $record->isExpired()) {
            abort(404, 'Preview link not found or expired.');
        }

        // Record first view
        if ($record->viewed_at === null) {
            $record->update(['viewed_at' => now()]);
        }

        return $record;
    }

    public function expire(string $contentType, string $contentId): void
    {
        CmsPreviewToken::withoutGlobalScopes()
            ->where('content_type', $contentType)
            ->where('content_id', $contentId)
            ->delete();
    }

    private function make(
        string $companyId,
        string $contentType,
        string $contentId,
        string $revisionId,
        string $createdByType,
    ): CmsPreviewToken {
        // Delete any existing preview tokens for this content
        $this->expire($contentType, $contentId);

        return CmsPreviewToken::create([
            'company_id' => $companyId,
            'content_type' => $contentType,
            'content_id' => $contentId,
            'revision_id' => $revisionId,
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
            'created_by_type' => $createdByType,
            'created_at' => now(),
        ]);
    }

    private function requireStagedRevision(CmsPage|CmsPost $content): CmsPageRevision
    {
        if ($content->staged_revision_id === null) {
            throw new RuntimeException('No staged revision exists for this content.');
        }

        $revision = CmsPageRevision::withoutGlobalScopes()->find($content->staged_revision_id);

        if ($revision === null) {
            throw new RuntimeException('Staged revision record not found.');
        }

        return $revision;
    }
}
