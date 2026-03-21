<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Models\CmsMedia;
use App\Models\CmsUser;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MediaDeleteTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private CmsUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create(['slug' => 'test']);
        $this->admin = CmsUser::factory()->admin()->create(['company_id' => $this->company->id]);
        app()->instance('current_company', $this->company);

        Storage::fake('local');
    }

    public function test_admin_can_soft_delete_media(): void
    {
        $media = CmsMedia::factory()->create([
            'company_id' => $this->company->id,
            'uploaded_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'cms')
            ->delete(route('admin.media.destroy', $media))
            ->assertRedirect(route('admin.media.index'));

        $this->assertSoftDeleted('cms_media', ['id' => $media->id]);
    }

    public function test_editor_cannot_delete_media(): void
    {
        $editor = CmsUser::factory()->editor()->create(['company_id' => $this->company->id]);
        $media = CmsMedia::factory()->create([
            'company_id' => $this->company->id,
            'uploaded_by' => $this->admin->id,
        ]);

        $this->actingAs($editor, 'cms')
            ->delete(route('admin.media.destroy', $media))
            ->assertStatus(403);
    }

    public function test_admin_cannot_delete_another_companys_media(): void
    {
        $otherCompany = Company::factory()->create();
        $otherMedia = CmsMedia::withoutGlobalScopes()->create([
            'company_id' => $otherCompany->id,
            'uploaded_by' => CmsUser::factory()->admin()->create(['company_id' => $otherCompany->id])->id,
            'filename' => 'other.jpg',
            'path' => 'media/other/photo.jpg',
            'disk' => 'local',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1000,
        ]);

        // HasCompanyScope makes cross-company records invisible → 404 (not 403)
        $this->actingAs($this->admin, 'cms')
            ->delete(route('admin.media.destroy', $otherMedia))
            ->assertStatus(404);
    }
}
