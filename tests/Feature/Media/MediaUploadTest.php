<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use App\Models\CmsMedia;
use App\Models\CmsUser;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MediaUploadTest extends TestCase
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

    public function test_admin_can_upload_jpeg(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $response = $this->actingAs($this->admin, 'cms')
            ->postJson(route('admin.media.store'), ['file' => $file]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'filename', 'url', 'width', 'height']);
        $this->assertDatabaseHas('cms_media', ['company_id' => $this->company->id, 'filename' => 'photo.jpg']);
    }

    public function test_admin_can_upload_png(): void
    {
        $file = UploadedFile::fake()->image('logo.png', 200, 200);

        $this->actingAs($this->admin, 'cms')
            ->postJson(route('admin.media.store'), ['file' => $file])
            ->assertStatus(201);
    }

    public function test_admin_can_upload_svg(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40"/></svg>';
        $file = UploadedFile::fake()->createWithContent('icon.svg', $svg);

        $this->actingAs($this->admin, 'cms')
            ->postJson(route('admin.media.store'), ['file' => $file])
            ->assertStatus(201);
    }

    public function test_wrong_mime_type_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($this->admin, 'cms')
            ->postJson(route('admin.media.store'), ['file' => $file])
            ->assertStatus(422);
    }

    public function test_oversized_file_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('big.jpg', 15_000, 'image/jpeg'); // 15 MB

        $this->actingAs($this->admin, 'cms')
            ->postJson(route('admin.media.store'), ['file' => $file])
            ->assertStatus(422);
    }

    public function test_guest_cannot_upload(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg');

        $this->postJson(route('admin.media.store'), ['file' => $file])
            ->assertStatus(401);
    }

    public function test_viewer_cannot_upload(): void
    {
        $viewer = CmsUser::factory()->viewer()->create(['company_id' => $this->company->id]);
        $file = UploadedFile::fake()->image('photo.jpg');

        $this->actingAs($viewer, 'cms')
            ->postJson(route('admin.media.store'), ['file' => $file])
            ->assertStatus(403);
    }

    public function test_media_is_scoped_to_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherAdmin = CmsUser::factory()->admin()->create(['company_id' => $otherCompany->id]);
        app()->instance('current_company', $otherCompany);

        $file = UploadedFile::fake()->image('other.jpg');

        $this->actingAs($otherAdmin, 'cms')
            ->postJson(route('admin.media.store'), ['file' => $file])
            ->assertStatus(201);

        app()->instance('current_company', $this->company);

        $this->assertSame(0, CmsMedia::where('company_id', $this->company->id)->count());
    }
}
