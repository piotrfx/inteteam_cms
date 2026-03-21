<?php

declare(strict_types=1);

namespace Tests\Feature\Revisions;

use App\Models\CmsPage;
use App\Models\Company;
use App\Services\PreviewTokenService;
use App\Services\RevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RevisionDiscardTest extends TestCase
{
    use RefreshDatabase;

    public function test_discard_clears_staged_revision_id(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();
        $service = app(RevisionService::class);

        $service->stagePageRevision($page, [], 'Test', 'user');
        $service->discardStaged($page);

        $this->assertNull($page->fresh()->staged_revision_id);
    }

    public function test_discard_does_not_touch_live_blocks(): void
    {
        $company = Company::factory()->create();
        $original = [['id' => 'x', 'type' => 'divider', 'data' => []]];
        $page = CmsPage::factory()->for($company)->create(['blocks' => $original]);
        $service = app(RevisionService::class);

        $service->stagePageRevision($page, [['id' => 'y', 'type' => 'heading', 'data' => []]], 'Changed', 'user');
        $service->discardStaged($page);

        $this->assertSame($original, $page->fresh()->blocks);
    }

    public function test_discard_is_noop_when_no_staged_revision(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();
        $service = app(RevisionService::class);

        $service->discardStaged($page); // no staged

        $this->assertNull($page->fresh()->staged_revision_id);
    }

    public function test_discard_deletes_orphaned_revision(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();
        $service = app(RevisionService::class);

        $revision = $service->stagePageRevision($page, [], 'Test', 'user');
        $this->assertDatabaseHas('cms_page_revisions', ['id' => $revision->id]);

        $service->discardStaged($page);

        $this->assertDatabaseMissing('cms_page_revisions', ['id' => $revision->id]);
    }

    public function test_discard_deletes_preview_tokens(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();
        $service = app(RevisionService::class);
        $previewService = app(PreviewTokenService::class);

        $service->stagePageRevision($page, [], 'Test', 'user');
        $previewService->createForPage($page);
        $this->assertDatabaseCount('cms_preview_tokens', 1);

        $service->discardStaged($page);

        $this->assertDatabaseCount('cms_preview_tokens', 0);
    }
}
