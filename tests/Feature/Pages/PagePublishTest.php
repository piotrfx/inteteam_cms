<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use App\Models\CmsPage;
use App\Models\CmsUser;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PagePublishTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private CmsUser $admin;
    private CmsUser $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create(['slug' => 'test']);
        $this->admin   = CmsUser::factory()->admin()->create(['company_id' => $this->company->id]);
        $this->editor  = CmsUser::factory()->editor()->create(['company_id' => $this->company->id]);
        app()->instance('current_company', $this->company);
    }

    public function test_admin_can_publish_draft_page(): void
    {
        $page = CmsPage::factory()->draft()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.pages.publish', $page->id))
            ->assertRedirect();

        $this->assertDatabaseHas('cms_pages', [
            'id'     => $page->id,
            'status' => 'published',
        ]);
        $this->assertNotNull($page->fresh()?->published_at);
    }

    public function test_admin_can_unpublish_page(): void
    {
        $page = CmsPage::factory()->published()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.pages.unpublish', $page->id))
            ->assertRedirect();

        $this->assertDatabaseHas('cms_pages', [
            'id'     => $page->id,
            'status' => 'draft',
        ]);
    }

    public function test_editor_cannot_publish_page(): void
    {
        $page = CmsPage::factory()->draft()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->editor, 'cms')
            ->post(route('admin.pages.publish', $page->id))
            ->assertStatus(403);
    }

    public function test_editor_cannot_unpublish_page(): void
    {
        $page = CmsPage::factory()->published()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->editor, 'cms')
            ->post(route('admin.pages.unpublish', $page->id))
            ->assertStatus(403);
    }

    public function test_publishing_sets_published_at_once(): void
    {
        $page = CmsPage::factory()->published()->create([
            'company_id'   => $this->company->id,
            'published_at' => now()->subDays(5),
        ]);

        $originalPublishedAt = $page->published_at;

        // Unpublish then re-publish; published_at must not reset
        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.pages.unpublish', $page->id));

        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.pages.publish', $page->id));

        $this->assertEquals(
            $originalPublishedAt->toDateTimeString(),
            $page->fresh()?->published_at?->toDateTimeString(),
        );
    }

    public function test_admin_cannot_publish_another_companys_page(): void
    {
        $other = Company::factory()->create(['slug' => 'other']);
        $page  = CmsPage::factory()->draft()->create(['company_id' => $other->id]);

        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.pages.publish', $page->id))
            ->assertStatus(404);
    }
}
