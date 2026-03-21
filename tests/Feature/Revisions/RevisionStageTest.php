<?php

declare(strict_types=1);

namespace Tests\Feature\Revisions;

use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\Company;
use App\Services\RevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RevisionStageTest extends TestCase
{
    use RefreshDatabase;

    public function test_staging_page_revision_creates_record(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create(['blocks' => []]);

        $blocks = [['id' => 'a', 'type' => 'heading', 'data' => ['text' => 'Hello', 'level' => 1]]];

        $service = app(RevisionService::class);
        $revision = $service->stagePageRevision($page, $blocks, 'Added heading', 'user', 'user-1');

        $this->assertDatabaseHas('cms_page_revisions', [
            'id' => $revision->id,
            'content_type' => 'page',
            'content_id' => $page->id,
            'summary' => 'Added heading',
            'created_by_type' => 'user',
            'created_by_id' => 'user-1',
        ]);

        $this->assertSame($blocks, $revision->blocks);
    }

    public function test_staging_page_sets_staged_revision_id(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();

        $service = app(RevisionService::class);
        $revision = $service->stagePageRevision($page, [], 'Test', 'user');

        $this->assertSame($revision->id, $page->fresh()->staged_revision_id);
    }

    public function test_staging_does_not_touch_live_blocks(): void
    {
        $company = Company::factory()->create();
        $original = [['id' => 'x', 'type' => 'divider', 'data' => []]];
        $page = CmsPage::factory()->for($company)->create(['blocks' => $original]);

        $service = app(RevisionService::class);
        $service->stagePageRevision($page, [['id' => 'y', 'type' => 'heading', 'data' => []]], 'Changed', 'user');

        $this->assertSame($original, $page->fresh()->blocks);
    }

    public function test_staging_post_revision_creates_record(): void
    {
        $company = Company::factory()->create();
        $post = CmsPost::factory()->for($company)->create(['blocks' => []]);

        $service = app(RevisionService::class);
        $revision = $service->stagePostRevision($post, [], 'Test post', 'ai_agent', 'claude');

        $this->assertDatabaseHas('cms_page_revisions', [
            'id' => $revision->id,
            'content_type' => 'post',
            'content_id' => $post->id,
            'created_by_type' => 'ai_agent',
            'created_by_id' => 'claude',
        ]);

        $this->assertSame($revision->id, $post->fresh()->staged_revision_id);
    }

    public function test_staging_replaces_previous_staged_revision(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();

        $service = app(RevisionService::class);
        $first = $service->stagePageRevision($page, [], 'First', 'user');
        $second = $service->stagePageRevision($page, [], 'Second', 'user');

        $this->assertSame($second->id, $page->fresh()->staged_revision_id);
        // Both revisions exist in DB (history is kept)
        $this->assertDatabaseHas('cms_page_revisions', ['id' => $first->id]);
        $this->assertDatabaseHas('cms_page_revisions', ['id' => $second->id]);
    }
}
