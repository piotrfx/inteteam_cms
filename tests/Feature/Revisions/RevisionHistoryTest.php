<?php

declare(strict_types=1);

namespace Tests\Feature\Revisions;

use App\Models\CmsPage;
use App\Models\CmsUser;
use App\Models\Company;
use App\Services\RevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RevisionHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_returns_revisions_in_descending_order(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();
        $service = app(RevisionService::class);

        $r1 = $service->stagePageRevision($page, [], 'First', 'user');
        $r2 = $service->stagePageRevision($page, [], 'Second', 'user');

        $history = $service->history($page);

        $this->assertCount(2, $history);
        $this->assertSame($r2->id, $history->first()->id);
        $this->assertSame($r1->id, $history->last()->id);
    }

    public function test_history_only_returns_own_content_revisions(): void
    {
        $company = Company::factory()->create();
        $page1 = CmsPage::factory()->for($company)->create();
        $page2 = CmsPage::factory()->for($company)->create();
        $service = app(RevisionService::class);

        $service->stagePageRevision($page1, [], 'Page 1', 'user');
        $service->stagePageRevision($page2, [], 'Page 2', 'user');

        $history = $service->history($page1);

        $this->assertCount(1, $history);
        $this->assertSame('Page 1', $history->first()->summary);
    }

    public function test_admin_can_view_page_revision_history(): void
    {
        $company = Company::factory()->create();
        $admin = CmsUser::factory()->for($company)->admin()->create();
        $page = CmsPage::factory()->for($company)->create();
        $service = app(RevisionService::class);

        $service->stagePageRevision($page, [], 'Test', 'user', $admin->id);

        $response = $this->actingAs($admin, 'cms')
            ->get(route('admin.pages.revisions', $page->id));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Revisions/Index'));
    }

    public function test_admin_can_restore_page_revision(): void
    {
        $company = Company::factory()->create();
        $admin = CmsUser::factory()->for($company)->admin()->create();
        $page = CmsPage::factory()->for($company)->create(['blocks' => []]);
        $service = app(RevisionService::class);

        $original = [['id' => 'a', 'type' => 'heading', 'data' => ['text' => 'Original', 'level' => 1]]];
        $r1 = $service->stagePageRevision($page, $original, 'Original heading', 'user');
        $service->publishStaged($page);

        // Now stage and then restore to r1
        $service->stagePageRevision($page, [], 'Empty', 'user');

        $response = $this->actingAs($admin, 'cms')
            ->post(route('admin.pages.revisions.restore', ['page' => $page->id, 'revision' => $r1->id]));

        $response->assertRedirect();
        $this->assertSame($original, $page->fresh()->stagedRevision?->blocks);
    }

    public function test_editor_cannot_restore_revision(): void
    {
        $company = Company::factory()->create();
        $editor = CmsUser::factory()->for($company)->editor()->create();
        $page = CmsPage::factory()->for($company)->create();
        $service = app(RevisionService::class);

        $revision = $service->stagePageRevision($page, [], 'Test', 'user');
        $service->publishStaged($page);

        $response = $this->actingAs($editor, 'cms')
            ->post(route('admin.pages.revisions.restore', ['page' => $page->id, 'revision' => $revision->id]));

        $response->assertForbidden();
    }
}
