<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Models\CmsMcpToken;
use App\Models\CmsPage;
use App\Models\CmsUser;
use App\Models\Company;
use App\Services\RevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class McpPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function bindCompany(Company $company): void
    {
        app()->instance('current_company', $company);
    }

    private function makeToken(Company $company, array $permissions): array
    {
        $raw = 'mcpsk_' . bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);

        $user = CmsUser::factory()->for($company)->create(['role' => 'admin']);

        CmsMcpToken::create([
            'company_id' => $company->id,
            'name' => 'Test',
            'token_hash' => $hash,
            'permissions' => $permissions,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        return ['raw' => $raw, 'user' => $user];
    }

    private function mcpCall(string $tool, array $args, string $rawToken): TestResponse
    {
        return $this->postJson('/mcp/v1', [
            'jsonrpc' => '2.0',
            'method' => 'tools/call',
            'params' => ['name' => $tool, 'arguments' => $args],
            'id' => 1,
        ], ['Authorization' => "Bearer {$rawToken}"]);
    }

    public function test_read_only_token_can_list_pages(): void
    {
        $company = Company::factory()->create();
        $this->bindCompany($company);
        ['raw' => $raw] = $this->makeToken($company, ['read']);

        $response = $this->mcpCall('list_pages', [], $raw);

        $response->assertOk()->assertJsonPath('id', 1);
        $result = json_decode(
            $response->json('result.content.0.text'),
            true,
        );
        $this->assertArrayHasKey('pages', $result);
    }

    public function test_read_only_token_cannot_update_page_blocks(): void
    {
        $company = Company::factory()->create();
        $this->bindCompany($company);

        $page = CmsPage::factory()->for($company)->create();
        ['raw' => $raw] = $this->makeToken($company, ['read']);

        $response = $this->mcpCall('update_page_blocks', [
            'page_id' => $page->id,
            'blocks' => [],
        ], $raw);

        $response->assertOk();
        $result = json_decode($response->json('result.content.0.text'), true);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Write permission', $result['error']);
    }

    public function test_write_token_can_stage_page_blocks(): void
    {
        $company = Company::factory()->create();
        $this->bindCompany($company);

        $page = CmsPage::factory()->for($company)->create(['blocks' => []]);
        ['raw' => $raw] = $this->makeToken($company, ['read', 'write']);

        $blocks = [['id' => 'abc', 'type' => 'heading', 'data' => ['text' => 'Hello', 'level' => 1]]];

        $response = $this->mcpCall('update_page_blocks', [
            'page_id' => $page->id,
            'blocks' => $blocks,
            'summary' => 'AI added heading',
        ], $raw);

        $response->assertOk();
        $result = json_decode($response->json('result.content.0.text'), true);
        $this->assertTrue($result['staged']);
        $this->assertNotNull($page->fresh()->staged_revision_id);
    }

    public function test_write_token_cannot_publish(): void
    {
        $company = Company::factory()->create();
        $this->bindCompany($company);

        $page = CmsPage::factory()->for($company)->create();
        app(RevisionService::class)->stagePageRevision($page, [], 'test', 'user');

        ['raw' => $raw] = $this->makeToken($company, ['read', 'write']);

        $response = $this->mcpCall('publish_staged', [
            'content_type' => 'page',
            'content_id' => $page->id,
        ], $raw);

        $response->assertOk();
        $result = json_decode($response->json('result.content.0.text'), true);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Publish permission', $result['error']);
    }

    public function test_publish_token_can_publish(): void
    {
        $company = Company::factory()->create();
        $this->bindCompany($company);

        $page = CmsPage::factory()->for($company)->create(['blocks' => []]);
        app(RevisionService::class)->stagePageRevision($page, [], 'test', 'user');

        ['raw' => $raw] = $this->makeToken($company, ['read', 'write', 'publish']);

        $response = $this->mcpCall('publish_staged', [
            'content_type' => 'page',
            'content_id' => $page->id,
        ], $raw);

        $response->assertOk();
        $result = json_decode($response->json('result.content.0.text'), true);
        $this->assertTrue($result['published']);
        $this->assertNull($page->fresh()->staged_revision_id);
    }
}
