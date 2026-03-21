<?php

declare(strict_types=1);

namespace Tests\Feature\Revisions;

use App\Models\CmsPage;
use App\Models\Company;
use App\Services\PreviewTokenService;
use App\Services\RevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class RevisionPublishTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_copies_blocks_to_live(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create(['blocks' => []]);
        $service = app(RevisionService::class);

        $blocks = [['id' => 'a', 'type' => 'heading', 'data' => ['text' => 'Live now', 'level' => 1]]];
        $service->stagePageRevision($page, $blocks, 'Updated heading', 'user');
        $service->publishStaged($page);

        $fresh = $page->fresh();
        $this->assertSame($blocks, $fresh->blocks);
        $this->assertNull($fresh->staged_revision_id);
        $this->assertNotNull($fresh->live_revision_id);
    }

    public function test_publishing_clears_staged_revision_id(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();
        $service = app(RevisionService::class);

        $service->stagePageRevision($page, [], 'Test', 'user');
        $service->publishStaged($page);

        $this->assertNull($page->fresh()->staged_revision_id);
    }

    public function test_publishing_sets_live_revision_id(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();
        $service = app(RevisionService::class);

        $revision = $service->stagePageRevision($page, [], 'Test', 'user');
        $service->publishStaged($page);

        $this->assertSame($revision->id, $page->fresh()->live_revision_id);
    }

    public function test_publishing_busts_redis_cache(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->published()->create(['slug' => 'about']);
        $service = app(RevisionService::class);

        Cache::put("cms:page:{$company->id}:about", '<html>old</html>', 300);
        $service->stagePageRevision($page, [], 'Test', 'user');
        $service->publishStaged($page);

        $this->assertNull(Cache::get("cms:page:{$company->id}:about"));
    }

    public function test_publishing_with_no_staged_revision_is_a_noop(): void
    {
        $company = Company::factory()->create();
        $blocks = [['id' => 'x', 'type' => 'divider', 'data' => []]];
        $page = CmsPage::factory()->for($company)->create(['blocks' => $blocks]);
        $service = app(RevisionService::class);

        $service->publishStaged($page); // no staged revision

        $this->assertSame($blocks, $page->fresh()->blocks);
    }

    public function test_publishing_deletes_preview_tokens(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();
        $service = app(RevisionService::class);
        $previewService = app(PreviewTokenService::class);

        $service->stagePageRevision($page, [], 'Test', 'user');
        $previewService->createForPage($page);

        $this->assertDatabaseCount('cms_preview_tokens', 1);

        $service->publishStaged($page);

        $this->assertDatabaseCount('cms_preview_tokens', 0);
    }
}
