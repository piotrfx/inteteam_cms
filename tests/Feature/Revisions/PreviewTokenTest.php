<?php

declare(strict_types=1);

namespace Tests\Feature\Revisions;

use App\Models\CmsPage;
use App\Models\CmsPreviewToken;
use App\Models\Company;
use App\Services\PreviewTokenService;
use App\Services\RevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

final class PreviewTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_preview_token_requires_staged_revision(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();

        $this->expectException(RuntimeException::class);

        app(PreviewTokenService::class)->createForPage($page);
    }

    public function test_create_for_page_returns_token_record(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();
        $service = app(RevisionService::class);
        $preview = app(PreviewTokenService::class);

        $service->stagePageRevision($page, [], 'Test', 'user');
        $token = $preview->createForPage($page);

        $this->assertDatabaseHas('cms_preview_tokens', [
            'content_type' => 'page',
            'content_id' => $page->id,
        ]);
        $this->assertSame(64, strlen($token->token));
        $this->assertFalse($token->isExpired());
    }

    public function test_token_expires_after_48_hours(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();
        $service = app(RevisionService::class);
        $preview = app(PreviewTokenService::class);

        $service->stagePageRevision($page, [], 'Test', 'user');
        $token = $preview->createForPage($page);

        $token->update(['expires_at' => now()->subSecond()]);

        $this->assertTrue($token->fresh()->isExpired());
    }

    public function test_validate_returns_token_record(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();
        $service = app(RevisionService::class);
        $preview = app(PreviewTokenService::class);

        $service->stagePageRevision($page, [], 'Test', 'user');
        $token = $preview->createForPage($page);

        $found = $preview->validate($token->token);

        $this->assertSame($token->id, $found->id);
    }

    public function test_validate_aborts_on_expired_token(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();
        $service = app(RevisionService::class);
        $preview = app(PreviewTokenService::class);

        $service->stagePageRevision($page, [], 'Test', 'user');
        $token = $preview->createForPage($page);
        $token->update(['expires_at' => now()->subSecond()]);

        $response = $this->get("/preview/{$token->token}");
        $response->assertNotFound();
    }

    public function test_validate_aborts_on_invalid_token(): void
    {
        $response = $this->get('/preview/invalid-token-xyz');
        $response->assertNotFound();
    }

    public function test_creating_new_token_replaces_old_one(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();
        $service = app(RevisionService::class);
        $preview = app(PreviewTokenService::class);

        $service->stagePageRevision($page, [], 'First', 'user');
        $first = $preview->createForPage($page);

        $service->stagePageRevision($page, [], 'Second', 'user');
        $second = $preview->createForPage($page);

        $this->assertDatabaseCount('cms_preview_tokens', 1);
        $this->assertSame($second->id, CmsPreviewToken::withoutGlobalScopes()->first()->id);
    }
}
