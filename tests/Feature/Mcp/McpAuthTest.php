<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Models\CmsMcpToken;
use App\Models\CmsUser;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class McpAuthTest extends TestCase
{
    use RefreshDatabase;

    private function bindCompany(Company $company): void
    {
        app()->instance('current_company', $company);
    }

    private function mcpPost(array $body, ?string $token = null): TestResponse
    {
        $headers = ['Accept' => 'application/json'];
        if ($token !== null) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $this->postJson('/mcp/v1', $body, $headers);
    }

    private function makeToken(Company $company, array $permissions = ['read']): array
    {
        $raw = 'mcpsk_' . bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);

        $user = CmsUser::factory()->for($company)->create(['role' => 'admin']);

        CmsMcpToken::create([
            'company_id' => $company->id,
            'name' => 'Test token',
            'token_hash' => $hash,
            'permissions' => $permissions,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        return ['raw' => $raw, 'user' => $user];
    }

    public function test_request_without_token_returns_401(): void
    {
        $company = Company::factory()->create();
        $this->bindCompany($company);

        $this->mcpPost(['jsonrpc' => '2.0', 'method' => 'tools/list', 'id' => 1])
            ->assertStatus(401)
            ->assertJson(['error' => ['code' => -32001]]);
    }

    public function test_request_with_wrong_token_returns_401(): void
    {
        $company = Company::factory()->create();
        $this->bindCompany($company);

        $this->mcpPost(['jsonrpc' => '2.0', 'method' => 'tools/list', 'id' => 1], 'invalid-token')
            ->assertStatus(401);
    }

    public function test_valid_token_allows_access(): void
    {
        $company = Company::factory()->create();
        $this->bindCompany($company);

        ['raw' => $raw] = $this->makeToken($company);

        $this->mcpPost(['jsonrpc' => '2.0', 'method' => 'tools/list', 'id' => 1], $raw)
            ->assertOk()
            ->assertJsonPath('jsonrpc', '2.0')
            ->assertJsonPath('id', 1);
    }

    public function test_revoked_token_is_rejected(): void
    {
        $company = Company::factory()->create();
        $this->bindCompany($company);

        ['raw' => $raw] = $this->makeToken($company);

        CmsMcpToken::where('token_hash', hash('sha256', $raw))
            ->update(['revoked_at' => now()]);

        $this->mcpPost(['jsonrpc' => '2.0', 'method' => 'tools/list', 'id' => 1], $raw)
            ->assertStatus(401);
    }

    public function test_expired_token_is_rejected(): void
    {
        $company = Company::factory()->create();
        $this->bindCompany($company);

        ['raw' => $raw] = $this->makeToken($company);

        CmsMcpToken::where('token_hash', hash('sha256', $raw))
            ->update(['expires_at' => now()->subDay()]);

        $this->mcpPost(['jsonrpc' => '2.0', 'method' => 'tools/list', 'id' => 1], $raw)
            ->assertStatus(401);
    }

    public function test_token_from_different_company_is_rejected(): void
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        // Token belongs to company2, but request is for company1
        ['raw' => $raw] = $this->makeToken($company2);

        $this->bindCompany($company1);

        $this->mcpPost(['jsonrpc' => '2.0', 'method' => 'tools/list', 'id' => 1], $raw)
            ->assertStatus(401);
    }

    public function test_invalid_jsonrpc_returns_error(): void
    {
        $company = Company::factory()->create();
        $this->bindCompany($company);

        ['raw' => $raw] = $this->makeToken($company);

        $this->mcpPost(['jsonrpc' => '1.0', 'method' => 'tools/list'], $raw)
            ->assertOk()
            ->assertJson(['error' => ['code' => -32600]]);
    }

    public function test_unknown_method_returns_error(): void
    {
        $company = Company::factory()->create();
        $this->bindCompany($company);

        ['raw' => $raw] = $this->makeToken($company);

        $this->mcpPost(['jsonrpc' => '2.0', 'method' => 'unknown/method', 'id' => 1], $raw)
            ->assertOk()
            ->assertJson(['error' => ['code' => -32601]]);
    }

    public function test_last_used_at_is_updated_on_valid_request(): void
    {
        $company = Company::factory()->create();
        $this->bindCompany($company);

        ['raw' => $raw] = $this->makeToken($company);

        $this->assertNull(CmsMcpToken::where('token_hash', hash('sha256', $raw))->value('last_used_at'));

        $this->mcpPost(['jsonrpc' => '2.0', 'method' => 'tools/list', 'id' => 1], $raw);

        $this->assertNotNull(CmsMcpToken::where('token_hash', hash('sha256', $raw))->value('last_used_at'));
    }
}
