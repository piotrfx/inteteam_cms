<?php

declare(strict_types=1);

namespace Tests\Feature\Crm;

use App\Models\CmsUser;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class CrmConnectionTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(Company $company): CmsUser
    {
        return CmsUser::factory()->for($company)->create(['role' => 'admin']);
    }

    private function bindCompany(Company $company): void
    {
        app()->instance('current_company', $company);
    }

    public function test_crm_settings_page_renders(): void
    {
        $company = Company::factory()->create();
        $user = $this->adminUser($company);
        $this->bindCompany($company);

        $response = $this->actingAs($user, 'cms')->get(route('admin.settings.crm'));

        $response->assertOk()
            ->assertInertia(fn ($p) => $p->component('Admin/Settings/CrmIntegration'));
    }

    public function test_crm_settings_page_forbidden_for_non_admin(): void
    {
        $company = Company::factory()->create();
        $user = CmsUser::factory()->for($company)->create(['role' => 'editor']);
        $this->bindCompany($company);

        $this->actingAs($user, 'cms')->get(route('admin.settings.crm'))->assertForbidden();
    }

    public function test_saving_crm_settings_stores_encrypted_api_key(): void
    {
        $company = Company::factory()->create();
        $user = $this->adminUser($company);
        $this->bindCompany($company);

        $this->actingAs($user, 'cms')->post(route('admin.settings.crm.update'), [
            'crm_base_url' => 'https://crm.test',
            'crm_company_id' => 'comp-abc',
            'crm_api_key' => 'my-secret-key',
        ])->assertRedirect();

        $company->refresh();
        $this->assertSame('https://crm.test', $company->crm_base_url);
        $this->assertSame('comp-abc', $company->crm_company_id);
        // API key must be stored encrypted, not plaintext
        $this->assertNotSame('my-secret-key', $company->crm_api_key);
        $this->assertSame('my-secret-key', Crypt::decryptString($company->crm_api_key));
    }

    public function test_saving_without_api_key_preserves_existing_key(): void
    {
        $company = Company::factory()->withCrm()->create();
        $user = $this->adminUser($company);
        $this->bindCompany($company);

        $originalKey = $company->crm_api_key;

        $this->actingAs($user, 'cms')->post(route('admin.settings.crm.update'), [
            'crm_base_url' => 'https://crm.test',
            'crm_company_id' => 'comp-1',
            'crm_api_key' => '',
        ])->assertRedirect();

        $this->assertSame($originalKey, $company->fresh()->crm_api_key);
    }

    public function test_test_connection_returns_ok_when_crm_responds(): void
    {
        Http::fake(['https://crm.test/api/v1/ping' => Http::response([], 200)]);

        $company = Company::factory()->withCrm()->create();
        $user = $this->adminUser($company);
        $this->bindCompany($company);

        $this->actingAs($user, 'cms')
            ->post(route('admin.settings.crm.test'))
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_test_connection_returns_error_when_crm_fails(): void
    {
        Http::fake(['https://crm.test/*' => Http::response([], 503)]);

        $company = Company::factory()->withCrm()->create();
        $user = $this->adminUser($company);
        $this->bindCompany($company);

        $this->actingAs($user, 'cms')
            ->post(route('admin.settings.crm.test'))
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_test_connection_returns_error_when_crm_not_configured(): void
    {
        $company = Company::factory()->create(); // no CRM creds
        $user = $this->adminUser($company);
        $this->bindCompany($company);

        $this->actingAs($user, 'cms')
            ->post(route('admin.settings.crm.test'))
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }
}
