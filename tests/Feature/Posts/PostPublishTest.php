<?php

declare(strict_types=1);

namespace Tests\Feature\Posts;

use App\Models\CmsPost;
use App\Models\CmsUser;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PostPublishTest extends TestCase
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

    public function test_admin_can_publish_draft_post(): void
    {
        $post = CmsPost::factory()->create([
            'company_id' => $this->company->id,
            'author_id'  => $this->admin->id,
            'status'     => 'draft',
        ]);

        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.posts.publish', $post->id))
            ->assertRedirect();

        $this->assertDatabaseHas('cms_posts', [
            'id'     => $post->id,
            'status' => 'published',
        ]);
        $this->assertNotNull($post->fresh()?->published_at);
    }

    public function test_admin_can_unpublish_post(): void
    {
        $post = CmsPost::factory()->published()->create([
            'company_id' => $this->company->id,
            'author_id'  => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.posts.unpublish', $post->id))
            ->assertRedirect();

        $this->assertDatabaseHas('cms_posts', [
            'id'     => $post->id,
            'status' => 'draft',
        ]);
    }

    public function test_editor_cannot_publish_post(): void
    {
        $post = CmsPost::factory()->create([
            'company_id' => $this->company->id,
            'author_id'  => $this->admin->id,
            'status'     => 'draft',
        ]);

        $this->actingAs($this->editor, 'cms')
            ->post(route('admin.posts.publish', $post->id))
            ->assertStatus(403);
    }

    public function test_editor_cannot_unpublish_post(): void
    {
        $post = CmsPost::factory()->published()->create([
            'company_id' => $this->company->id,
            'author_id'  => $this->admin->id,
        ]);

        $this->actingAs($this->editor, 'cms')
            ->post(route('admin.posts.unpublish', $post->id))
            ->assertStatus(403);
    }

    public function test_publishing_preserves_original_published_at(): void
    {
        $post = CmsPost::factory()->published()->create([
            'company_id'   => $this->company->id,
            'author_id'    => $this->admin->id,
            'published_at' => now()->subDays(5),
        ]);

        $originalPublishedAt = $post->published_at;

        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.posts.unpublish', $post->id));

        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.posts.publish', $post->id));

        $this->assertEquals(
            $originalPublishedAt->toDateTimeString(),
            $post->fresh()?->published_at?->toDateTimeString(),
        );
    }

    public function test_admin_cannot_publish_another_companys_post(): void
    {
        $other = Company::factory()->create(['slug' => 'other']);
        $post  = CmsPost::factory()->create([
            'company_id' => $other->id,
            'author_id'  => CmsUser::factory()->create(['company_id' => $other->id])->id,
            'status'     => 'draft',
        ]);

        $this->actingAs($this->admin, 'cms')
            ->post(route('admin.posts.publish', $post->id))
            ->assertStatus(404);
    }
}
