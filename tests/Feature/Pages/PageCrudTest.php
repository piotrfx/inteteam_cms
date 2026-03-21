<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use App\Models\CmsPage;
use App\Models\CmsUser;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PageCrudTest extends TestCase
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

    // ── Index ────────────────────────────────────────────────────────────────

    public function test_admin_can_view_pages_index(): void
    {
        CmsPage::factory()->count(3)->create(['company_id' => $this->company->id]);

        $this->actingAs($this->admin, 'cms')
            ->get(route('admin.pages.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Admin/Pages/Index')->has('pages', 3));
    }

    public function test_unauthenticated_user_is_redirected_from_index(): void
    {
        $this->get(route('admin.pages.index'))->assertRedirect();
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function test_admin_can_view_create_page(): void
    {
        $this->actingAs($this->admin, 'cms')
            ->get(route('admin.pages.create'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Admin/Pages/Create'));
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function test_admin_can_create_custom_page(): void
    {
        $response = $this->actingAs($this->admin, 'cms')
            ->post(route('admin.pages.store'), [
                'title'  => 'Our Story',
                'slug'   => 'our-story',
                'type'   => 'custom',
                'status' => 'draft',
                'blocks' => [],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cms_pages', [
            'company_id' => $this->company->id,
            'title'      => 'Our Story',
            'slug'       => 'our-story',
            'type'       => 'custom',
            'status'     => 'draft',
        ]);
    }

    public function test_fixed_type_slug_is_forced(): void
    {
        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.pages.store'), [
                'title'  => 'Home Page',
                'slug'   => 'whatever-slug',
                'type'   => 'home',
                'status' => 'draft',
                'blocks' => [],
            ]);

        // PageService forces slug to 'home' for type=home
        $this->assertDatabaseHas('cms_pages', [
            'company_id' => $this->company->id,
            'slug'       => 'home',
            'type'       => 'home',
        ]);
    }

    public function test_duplicate_fixed_type_returns_error(): void
    {
        CmsPage::factory()->create([
            'company_id' => $this->company->id,
            'type'       => 'home',
            'slug'       => 'home',
        ]);

        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.pages.store'), [
                'title'  => 'Home 2',
                'slug'   => 'home-2',
                'type'   => 'home',
                'status' => 'draft',
                'blocks' => [],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('type');
    }

    public function test_editor_can_create_page(): void
    {
        $this->actingAs($this->editor, 'cms')
            ->post(route('admin.pages.store'), [
                'title'  => 'Editor Page',
                'slug'   => 'editor-page',
                'type'   => 'custom',
                'status' => 'draft',
                'blocks' => [],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cms_pages', ['title' => 'Editor Page']);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.pages.store'), [])
            ->assertSessionHasErrors(['title', 'slug', 'type', 'status']);
    }

    public function test_store_validates_slug_format(): void
    {
        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.pages.store'), [
                'title'  => 'Bad Slug',
                'slug'   => 'Bad Slug!',
                'type'   => 'custom',
                'status' => 'draft',
            ])
            ->assertSessionHasErrors('slug');
    }

    // ── Edit / Update ────────────────────────────────────────────────────────

    public function test_admin_can_view_edit_page(): void
    {
        $page = CmsPage::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->admin, 'cms')
            ->get(route('admin.pages.edit', $page->id))
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p->component('Admin/Pages/Edit')->has('page'));
    }

    public function test_admin_can_update_page(): void
    {
        $page = CmsPage::factory()->create([
            'company_id' => $this->company->id,
            'title'      => 'Old Title',
        ]);

        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.pages.update', $page->id), [
                'title'  => 'New Title',
                'slug'   => 'new-title',
                'status' => 'draft',
                'blocks' => [],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cms_pages', [
            'id'    => $page->id,
            'title' => 'New Title',
        ]);
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function test_admin_can_delete_page(): void
    {
        $page = CmsPage::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->admin, 'cms')
            ->delete(route('admin.pages.destroy', $page->id))
            ->assertRedirect(route('admin.pages.index'));

        $this->assertSoftDeleted('cms_pages', ['id' => $page->id]);
    }

    public function test_editor_cannot_delete_page(): void
    {
        $page = CmsPage::factory()->create(['company_id' => $this->company->id]);

        $this->actingAs($this->editor, 'cms')
            ->delete(route('admin.pages.destroy', $page->id))
            ->assertStatus(403);
    }

    // ── Multi-tenant isolation ───────────────────────────────────────────────

    public function test_admin_cannot_edit_another_companys_page(): void
    {
        $other = Company::factory()->create(['slug' => 'other']);
        $page  = CmsPage::factory()->create(['company_id' => $other->id]);

        // HasCompanyScope makes cross-company records invisible → 404
        $this->actingAs($this->admin, 'cms')
            ->get(route('admin.pages.edit', $page->id))
            ->assertStatus(404);
    }

    public function test_admin_cannot_delete_another_companys_page(): void
    {
        $other = Company::factory()->create(['slug' => 'other']);
        $page  = CmsPage::factory()->create(['company_id' => $other->id]);

        $this->actingAs($this->admin, 'cms')
            ->delete(route('admin.pages.destroy', $page->id))
            ->assertStatus(404);
    }
}
