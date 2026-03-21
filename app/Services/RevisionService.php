<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CmsPage;
use App\Models\CmsPageRevision;
use App\Models\CmsPost;
use App\Models\CmsPreviewToken;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class RevisionService
{
    public function stagePageRevision(
        CmsPage $page,
        array $blocks,
        string $summary,
        string $createdByType,
        ?string $createdById = null,
        ?string $aiSessionId = null,
    ): CmsPageRevision {
        $revision = CmsPageRevision::create([
            'company_id' => $page->company_id,
            'content_type' => 'page',
            'content_id' => $page->id,
            'blocks' => $blocks,
            'summary' => $summary,
            'created_by_type' => $createdByType,
            'created_by_id' => $createdById,
            'ai_session_id' => $aiSessionId,
            'created_at' => now(),
        ]);

        $page->update(['staged_revision_id' => $revision->id]);

        Log::info('Staged page revision', [
            'revision_id' => $revision->id,
            'page_id' => $page->id,
            'slug' => $page->slug,
        ]);

        return $revision;
    }

    public function stagePostRevision(
        CmsPost $post,
        array $blocks,
        string $summary,
        string $createdByType,
        ?string $createdById = null,
        ?string $aiSessionId = null,
    ): CmsPageRevision {
        $revision = CmsPageRevision::create([
            'company_id' => $post->company_id,
            'content_type' => 'post',
            'content_id' => $post->id,
            'blocks' => $blocks,
            'summary' => $summary,
            'created_by_type' => $createdByType,
            'created_by_id' => $createdById,
            'ai_session_id' => $aiSessionId,
            'created_at' => now(),
        ]);

        $post->update(['staged_revision_id' => $revision->id]);

        Log::info('Staged post revision', [
            'revision_id' => $revision->id,
            'post_id' => $post->id,
            'slug' => $post->slug,
        ]);

        return $revision;
    }

    public function publishStaged(CmsPage|CmsPost $content): void
    {
        $staged = $content->staged_revision_id
            ? CmsPageRevision::withoutGlobalScopes()->find($content->staged_revision_id)
            : null;

        if ($staged === null) {
            return;
        }

        $content->update([
            'live_revision_id' => $staged->id,
            'blocks' => $staged->blocks,
            'staged_revision_id' => null,
        ]);

        // Expire any preview tokens for this content
        CmsPreviewToken::withoutGlobalScopes()
            ->where('content_type', $content instanceof CmsPage ? 'page' : 'post')
            ->where('content_id', $content->id)
            ->delete();

        // Bust Redis cache
        $this->bustCache($content);

        Log::info('Published staged revision', [
            'revision_id' => $staged->id,
            'content' => class_basename($content),
            'id' => $content->id,
        ]);
    }

    public function discardStaged(CmsPage|CmsPost $content): void
    {
        if ($content->staged_revision_id === null) {
            return;
        }

        $stagedId = $content->staged_revision_id;

        $content->update(['staged_revision_id' => null]);

        // Expire any preview tokens for this content
        CmsPreviewToken::withoutGlobalScopes()
            ->where('content_type', $content instanceof CmsPage ? 'page' : 'post')
            ->where('content_id', $content->id)
            ->delete();

        // Delete the orphaned revision if nothing references it as live
        CmsPageRevision::withoutGlobalScopes()
            ->where('id', $stagedId)
            ->whereNotExists(function ($query) use ($stagedId): void {
                $query->from('cms_pages')->where('live_revision_id', $stagedId);
            })
            ->whereNotExists(function ($query) use ($stagedId): void {
                $query->from('cms_posts')->where('live_revision_id', $stagedId);
            })
            ->delete();

        Log::info('Discarded staged revision', [
            'content' => class_basename($content),
            'id' => $content->id,
        ]);
    }

    public function rollBackTo(CmsPage|CmsPost $content, CmsPageRevision $revision): CmsPageRevision
    {
        $summary = "Rolled back to revision from {$revision->created_at?->toDateTimeString()}";

        if ($content instanceof CmsPage) {
            return $this->stagePageRevision(
                page: $content,
                blocks: $revision->blocks,
                summary: $summary,
                createdByType: 'user',
                createdById: $content->created_by ?? null,
            );
        }

        return $this->stagePostRevision(
            post: $content,
            blocks: $revision->blocks,
            summary: $summary,
            createdByType: 'user',
        );
    }

    /** @return Collection<int, CmsPageRevision> */
    public function history(CmsPage|CmsPost $content, int $limit = 20): Collection
    {
        return CmsPageRevision::withoutGlobalScopes()
            ->where('company_id', $content->company_id)
            ->where('content_type', $content instanceof CmsPage ? 'page' : 'post')
            ->where('content_id', $content->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    private function bustCache(CmsPage|CmsPost $content): void
    {
        if ($content instanceof CmsPage) {
            Cache::forget("cms:page:{$content->company_id}:{$content->slug}");
        } else {
            Cache::forget("cms:post:{$content->company_id}:{$content->slug}");
        }
    }
}
