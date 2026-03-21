<?php

declare(strict_types=1);

namespace Tests\Feature\Crm;

use App\Exceptions\CrmConnectionException;
use App\Services\CrmApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class CrmApiClientTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function client(string $baseUrl = 'https://crm.test'): CrmApiClient
    {
        return new CrmApiClient($baseUrl, 'test-key', 'comp-1');
    }

    public function test_test_connection_returns_true_on_200(): void
    {
        Http::fake(['https://crm.test/api/v1/ping' => Http::response([], 200)]);

        $this->assertTrue($this->client()->testConnection());
    }

    public function test_test_connection_returns_false_on_error(): void
    {
        Http::fake(['https://crm.test/api/v1/ping' => Http::response([], 500)]);

        $this->assertFalse($this->client()->testConnection());
    }

    public function test_gallery_fetches_from_crm(): void
    {
        $items = [['url' => 'https://example.com/img.jpg', 'alt' => 'Test']];
        Http::fake(['https://crm.test/api/v1/galleries/main*' => Http::response($items, 200)]);

        $result = $this->client()->gallery('main');

        $this->assertSame($items, $result);
    }

    public function test_gallery_caches_result(): void
    {
        $items = [['url' => 'https://example.com/img.jpg', 'alt' => 'Test']];
        Http::fake(['https://crm.test/api/v1/galleries/main*' => Http::response($items, 200)]);

        Cache::flush();
        $this->client()->gallery('main');
        $this->client()->gallery('main'); // second call should use cache

        Http::assertSentCount(1);
    }

    public function test_gallery_throws_on_http_error(): void
    {
        Http::fake(['https://crm.test/*' => Http::response([], 503)]);

        $this->expectException(CrmConnectionException::class);
        $this->client()->gallery('main');
    }

    public function test_products_calls_storefront_endpoint(): void
    {
        $products = [['name' => 'Phone Screen', 'price' => 49.99]];
        Http::fake(['https://crm.test/api/v1/storefront/comp-1/products*' => Http::response($products, 200)]);

        $result = $this->client()->products(null, 5);

        $this->assertSame($products, $result);
    }

    public function test_business_updates_calls_embed_endpoint(): void
    {
        $updates = [['title' => 'Now open Sundays', 'body' => 'We are open.']];
        Http::fake(['https://crm.test/api/v1/embed/comp-1/updates*' => Http::response($updates, 200)]);

        $result = $this->client()->businessUpdates(3);

        $this->assertSame($updates, $result);
    }

    public function test_submit_form_posts_data_to_crm(): void
    {
        Http::fake(['https://crm.test/api/v1/forms/contact/submit' => Http::response(['status' => 'ok'], 200)]);

        $result = $this->client()->submitForm('contact', ['name' => 'Alice', 'email' => 'a@example.com']);

        $this->assertSame(['status' => 'ok'], $result);
        Http::assertSent(fn ($req) => $req->url() === 'https://crm.test/api/v1/forms/contact/submit'
            && $req->data()['name'] === 'Alice');
    }

    public function test_submit_form_throws_on_failure(): void
    {
        Http::fake(['https://crm.test/*' => Http::response([], 422)]);

        $this->expectException(CrmConnectionException::class);
        $this->client()->submitForm('contact', []);
    }
}
