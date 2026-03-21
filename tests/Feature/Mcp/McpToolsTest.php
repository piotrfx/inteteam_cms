<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Models\CmsMcpToken;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\CmsUser;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class McpToolsTest extends TestCase
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

    private function makeWriteToken(Company $company): string
    {
        $raw = 'mcpsk_' . bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $user = CmsUser::factory()->for($company)->create(['role' => 'admin']);

        CmsMcpToken::create([
            'company_id' => $company->id,
            'name' => 'Test',
            'token_hash' => $hash,
            'permissions' => ['read', 'write'],
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        return $raw;
    }

    private function mcpCall(string $tool, array $args, string $rawToken, Company $company): array
    {
        $this->bindCompany($company);

        $response = $this->postJson('/mcp/v1', [
            'jsonrpc' => '2.0',
            'method' => 'tools/call',
            'params' => ['name' => $tool, 'arguments' => $args],
            'id' => 1,
        ], ['Authorization' => "Bearer {$rawToken}"]);

        return json_decode($response->json('result.content.0.text'), true) ?? [];
    }

    public function test_initialize_returns_server_info(): void
    {
        $company = Company::factory()->create();
        $this->bindCompany($company);

        $raw = $this->makeWriteToken($company);

        $response = $this->postJson('/mcp/v1', [
            'jsonrpc' => '2.0',
            'method' => 'initialize',
            'id' => 1,
        ], ['Authorization' => "Bearer {$raw}"]);

        $response->assertOk()
            ->assertJsonPath('result.serverInfo.name', 'inteteam-cms');
    }

    public function test_tools_list_returns_all_tools(): void
    {
        $company = Company::factory()->create();
        $this->bindCompany($company);
        $raw = $this->makeWriteToken($company);

        $response = $this->postJson('/mcp/v1', [
            'jsonrpc' => '2.0',
            'method' => 'tools/list',
            'id' => 1,
        ], ['Authorization' => "Bearer {$raw}"]);

        $tools = $response->json('result.tools');
        $this->assertIsArray($tools);
        $names = array_column($tools, 'name');
        $this->assertContains('list_pages', $names);
        $this->assertContains('get_page', $names);
        $this->assertContains('update_page_blocks', $names);
        $this->assertContains('publish_staged', $names);
        $this->assertGreaterThanOrEqual(15, count($tools));
    }

    public function test_list_pages_returns_pages(): void
    {
        $company = Company::factory()->create();
        CmsPage::factory()->for($company)->create(['title' => 'Home', 'slug' => 'home']);
        CmsPage::factory()->for($company)->create(['title' => 'About', 'slug' => 'about']);
        $raw = $this->makeWriteToken($company);

        $result = $this->mcpCall('list_pages', [], $raw, $company);

        $this->assertCount(2, $result['pages']);
        $titles = array_column($result['pages'], 'title');
        $this->assertContains('Home', $titles);
    }

    public function test_get_page_by_slug(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create(['slug' => 'about', 'blocks' => []]);
        $raw = $this->makeWriteToken($company);

        $result = $this->mcpCall('get_page', ['slug' => 'about'], $raw, $company);

        $this->assertSame($page->id, $result['page']['id']);
        $this->assertSame('about', $result['page']['slug']);
        $this->assertArrayHasKey('blocks', $result['page']);
    }

    public function test_get_page_returns_error_for_missing(): void
    {
        $company = Company::factory()->create();
        $raw = $this->makeWriteToken($company);

        $result = $this->mcpCall('get_page', ['id' => 'nonexistent'], $raw, $company);

        $this->assertArrayHasKey('error', $result);
    }

    public function test_list_posts_returns_posts(): void
    {
        $company = Company::factory()->create();
        CmsPost::factory()->for($company)->create(['title' => 'First Post']);
        $raw = $this->makeWriteToken($company);

        $result = $this->mcpCall('list_posts', [], $raw, $company);

        $this->assertCount(1, $result['posts']);
        $this->assertSame('First Post', $result['posts'][0]['title']);
    }

    public function test_update_page_seo_updates_fields(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create();
        $raw = $this->makeWriteToken($company);

        $result = $this->mcpCall('update_page_seo', [
            'page_id' => $page->id,
            'seo_title' => 'New SEO Title',
            'seo_description' => 'New description',
        ], $raw, $company);

        $this->assertTrue($result['updated']);
        $this->assertSame('New SEO Title', $page->fresh()->seo_title);
    }

    public function test_discard_staged_removes_staged_revision(): void
    {
        $company = Company::factory()->create();
        $page = CmsPage::factory()->for($company)->create(['blocks' => []]);
        $raw = $this->makeWriteToken($company);

        // First stage something
        $this->mcpCall('update_page_blocks', [
            'page_id' => $page->id,
            'blocks' => [['id' => 'x', 'type' => 'divider', 'data' => []]],
        ], $raw, $company);

        $this->assertNotNull($page->fresh()->staged_revision_id);

        // Then discard
        $result = $this->mcpCall('discard_staged', [
            'content_type' => 'page',
            'content_id' => $page->id,
        ], $raw, $company);

        $this->assertTrue($result['discarded']);
        $this->assertNull($page->fresh()->staged_revision_id);
    }

    public function test_get_site_settings_returns_company_data(): void
    {
        $company = Company::factory()->create(['name' => 'ACME Repairs', 'seo_site_name' => 'ACME']);
        $raw = $this->makeWriteToken($company);

        $result = $this->mcpCall('get_site_settings', [], $raw, $company);

        $this->assertSame('ACME Repairs', $result['settings']['name']);
        $this->assertSame('ACME', $result['settings']['seo_site_name']);
    }
}
