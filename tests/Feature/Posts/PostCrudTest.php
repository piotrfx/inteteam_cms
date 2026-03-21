<?php

declare(strict_types=1);

namespace Tests\Feature\Posts;

use App\Models\CmsPost;
use App\Models\CmsUser;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PostCrudTest extends TestCase
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

    public function test_admin_can_view_posts_index(): void
    {
        CmsPost::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'author_id'  => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'cms')
            ->get(route('admin.posts.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Admin/Posts/Index')->has('posts', 3));
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->get(route('admin.posts.index'))->assertRedirect();
    }

    // ── Create ───────────────────────────────────────────────────────────────

    public function test_admin_can_view_create_post(): void
    {
        $this->actingAs($this->admin, 'cms')
            ->get(route('admin.posts.create'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->component('Admin/Posts/Create'));
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function test_admin_can_create_post(): void
    {
        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.posts.store'), [
                'title'  => 'My First Post',
                'slug'   => 'my-first-post',
                'status' => 'draft',
                'blocks' => [],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cms_posts', [
            'company_id' => $this->company->id,
            'title'      => 'My First Post',
            'slug'       => 'my-first-post',
            'author_id'  => $this->admin->id,
        ]);
    }

    public function test_editor_can_create_post(): void
    {
        $this->actingAs($this->editor, 'cms')
            ->post(route('admin.posts.store'), [
                'title'  => 'Editor Post',
                'slug'   => 'editor-post',
                'status' => 'draft',
                'blocks' => [],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cms_posts', ['title' => 'Editor Post']);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.posts.store'), [])
            ->assertSessionHasErrors(['title', 'slug', 'status']);
    }

    public function test_store_validates_slug_format(): void
    {
        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.posts.store'), [
                'title'  => 'Bad',
                'slug'   => 'Bad Slug!',
                'status' => 'draft',
            ])
            ->assertSessionHasErrors('slug');
    }

    // ── Edit / Update ────────────────────────────────────────────────────────

    public function test_admin_can_view_edit_post(): void
    {
        $post = CmsPost::factory()->create([
            'company_id' => $this->company->id,
            'author_id'  => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'cms')
            ->get(route('admin.posts.edit', $post->id))
            ->assertStatus(200)
            ->assertInertia(fn ($p) => $p->component('Admin/Posts/Edit')->has('post'));
    }

    public function test_admin_can_update_post(): void
    {
        $post = CmsPost::factory()->create([
            'company_id' => $this->company->id,
            'author_id'  => $this->admin->id,
            'title'      => 'Old Title',
        ]);

        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.posts.update', $post->id), [
                'title'  => 'New Title',
                'slug'   => 'new-title',
                'status' => 'draft',
                'blocks' => [],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cms_posts', [
            'id'    => $post->id,
            'title' => 'New Title',
        ]);
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function test_admin_can_delete_post(): void
    {
        $post = CmsPost::factory()->create([
            'company_id' => $this->company->id,
            'author_id'  => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'cms')
            ->delete(route('admin.posts.destroy', $post->id))
            ->assertRedirect(route('admin.posts.index'));

        $this->assertSoftDeleted('cms_posts', ['id' => $post->id]);
    }

    public function test_editor_cannot_delete_post(): void
    {
        $post = CmsPost::factory()->create([
            'company_id' => $this->company->id,
            'author_id'  => $this->admin->id,
        ]);

        $this->actingAs($this->editor, 'cms')
            ->delete(route('admin.posts.destroy', $post->id))
            ->assertStatus(403);
    }

    // ── Multi-tenant isolation ───────────────────────────────────────────────

    public function test_admin_cannot_edit_another_companys_post(): void
    {
        $other = Company::factory()->create(['slug' => 'other']);
        $post  = CmsPost::factory()->create([
            'company_id' => $other->id,
            'author_id'  => CmsUser::factory()->create(['company_id' => $other->id])->id,
        ]);

        // HasCompanyScope makes cross-company records invisible → 404
        $this->actingAs($this->admin, 'cms')
            ->get(route('admin.posts.edit', $post->id))
            ->assertStatus(404);
    }

    public function test_admin_cannot_delete_another_companys_post(): void
    {
        $other = Company::factory()->create(['slug' => 'other']);
        $post  = CmsPost::factory()->create([
            'company_id' => $other->id,
            'author_id'  => CmsUser::factory()->create(['company_id' => $other->id])->id,
        ]);

        $this->actingAs($this->admin, 'cms')
            ->delete(route('admin.posts.destroy', $post->id))
            ->assertStatus(404);
    }
}
