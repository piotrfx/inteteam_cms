<?php

declare(strict_types=1);

namespace Tests\Feature\Crm;

use App\Models\Company;
use App\Services\BlockRendererService;
use App\Services\CrmApiClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class CrmBlockRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function makeRenderer(Company $company): BlockRendererService
    {
        return new BlockRendererService(
            theme: 'default',
            company: $company,
            crmFactory: app(CrmApiClientFactory::class),
        );
    }

    public function test_gallery_block_renders_images(): void
    {
        Http::fake([
            '*/api/v1/galleries/test-gallery' => Http::response([
                'items' => [
                    ['url' => 'https://example.com/photo.jpg', 'alt' => 'Photo'],
                ],
            ], 200),
        ]);

        $company = Company::factory()->withCrm()->create();
        $renderer = $this->makeRenderer($company);

        $html = $renderer->render([[
            'id' => '1',
            'type' => 'gallery',
            'data' => ['gallery_slug' => 'test-gallery', 'columns' => 2],
        ]]);

        $this->assertStringContainsString('cms-gallery', $html);
        $this->assertStringContainsString('https://example.com/photo.jpg', $html);
    }

    public function test_gallery_block_renders_error_partial_when_crm_fails(): void
    {
        Http::fake(['*' => Http::response([], 503)]);

        $company = Company::factory()->withCrm()->create();
        $renderer = $this->makeRenderer($company);

        $html = $renderer->render([[
            'id' => '1',
            'type' => 'gallery',
            'data' => ['gallery_slug' => 'main'],
        ]]);

        $this->assertStringContainsString('cms-block-error', $html);
        $this->assertStringNotContainsString('cms-gallery', $html);
    }

    public function test_gallery_block_renders_error_when_crm_not_configured(): void
    {
        $company = Company::factory()->create(); // no CRM
        $renderer = $this->makeRenderer($company);

        $html = $renderer->render([[
            'id' => '1',
            'type' => 'gallery',
            'data' => ['gallery_slug' => 'main'],
        ]]);

        $this->assertStringContainsString('cms-block-error', $html);
    }

    public function test_business_updates_block_renders_titles(): void
    {
        Http::fake([
            '*/api/v1/embed/*/updates*' => Http::response([
                ['title' => 'Grand opening', 'body' => 'We are open!', 'published_at' => '2026-01-15'],
            ], 200),
        ]);

        $company = Company::factory()->withCrm()->create();
        $renderer = $this->makeRenderer($company);

        $html = $renderer->render([[
            'id' => '2',
            'type' => 'business_updates',
            'data' => ['limit' => 3],
        ]]);

        $this->assertStringContainsString('Grand opening', $html);
        $this->assertStringContainsString('cms-business-updates', $html);
    }

    public function test_non_crm_block_is_not_routed_through_crm(): void
    {
        Http::fake(); // should not be called

        $company = Company::factory()->create(); // no CRM creds needed
        $renderer = $this->makeRenderer($company);

        $html = $renderer->render([[
            'id' => '3',
            'type' => 'heading',
            'data' => ['text' => 'Hello', 'level' => 1],
        ]]);

        Http::assertNothingSent();
        $this->assertStringContainsString('Hello', $html);
    }

    public function test_multiple_blocks_rendered_in_order(): void
    {
        Http::fake([
            '*/api/v1/embed/*/updates*' => Http::response([
                ['title' => 'News item', 'body' => 'Body.'],
            ], 200),
        ]);

        $company = Company::factory()->withCrm()->create();
        $renderer = $this->makeRenderer($company);

        $html = $renderer->render([
            ['id' => '1', 'type' => 'heading',          'data' => ['text' => 'Welcome', 'level' => 2]],
            ['id' => '2', 'type' => 'business_updates', 'data' => ['limit' => 1]],
        ]);

        $this->assertStringContainsString('Welcome', $html);
        $this->assertStringContainsString('News item', $html);
        $this->assertLessThan(strpos($html, 'News item'), strpos($html, 'Welcome'));
    }
}
