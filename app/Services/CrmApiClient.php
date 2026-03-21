<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CrmConnectionException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class CrmApiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $companyId,
    ) {}

    public function testConnection(): bool
    {
        try {
            $response = $this->http()->get('/api/v1/ping');

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function galleries(): array
    {
        return $this->cached("crm:{$this->companyId}:galleries", 30, function (): array {
            return $this->get('/api/v1/galleries');
        });
    }

    /** @return array<string, mixed> */
    public function gallery(string $slug): array
    {
        return $this->cached("crm:{$this->companyId}:gallery:{$slug}", 5, function () use ($slug): array {
            return $this->get("/api/v1/galleries/{$slug}");
        });
    }

    /** @return array<string, mixed> */
    public function formSchema(string $slug): array
    {
        return $this->cached("crm:{$this->companyId}:form:{$slug}", 15, function () use ($slug): array {
            return $this->get("/api/v1/forms/{$slug}");
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function submitForm(string $slug, array $data): array
    {
        try {
            $response = $this->http()->post("/api/v1/forms/{$slug}/submit", $data);

            if (! $response->successful()) {
                throw new CrmConnectionException("Form submit failed: HTTP {$response->status()}");
            }

            /** @var array<string, mixed> */
            return (array) ($response->json() ?? []);
        } catch (ConnectionException $e) {
            throw new CrmConnectionException("CRM unreachable: {$e->getMessage()}", previous: $e);
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function products(?string $categorySlug = null, int $limit = 12): array
    {
        $key = "crm:{$this->companyId}:products:{$categorySlug}:{$limit}";

        return $this->cached($key, 5, function () use ($categorySlug, $limit): array {
            $params = ['limit' => $limit];
            if ($categorySlug !== null) {
                $params['category'] = $categorySlug;
            }

            return $this->get("/api/v1/storefront/{$this->companyId}/products", $params);
        });
    }

    /** @return array<int, array<string, mixed>> */
    public function productCategories(): array
    {
        return $this->cached("crm:{$this->companyId}:product-categories", 30, function (): array {
            return $this->get("/api/v1/storefront/{$this->companyId}/categories");
        });
    }

    /** @return array<int, array<string, mixed>> */
    public function businessUpdates(int $limit = 5): array
    {
        return $this->cached("crm:{$this->companyId}:updates:{$limit}", 5, function () use ($limit): array {
            return $this->get("/api/v1/embed/{$this->companyId}/updates", ['limit' => $limit]);
        });
    }

    /** @return array<string, mixed> */
    public function storefrontConfig(): array
    {
        return $this->cached("crm:{$this->companyId}:storefront-config", 30, function (): array {
            return $this->get("/api/v1/storefront/{$this->companyId}/config");
        });
    }

    /**
     * @template T of array
     *
     * @param  \Closure(): T  $callback
     * @return T
     */
    private function cached(string $key, int $ttlMinutes, \Closure $callback): array
    {
        /** @var T */
        return Cache::remember($key, now()->addMinutes($ttlMinutes), $callback);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>|array<int, array<string, mixed>>
     */
    private function get(string $path, array $query = []): array
    {
        try {
            $response = $this->http()->get($path, $query);

            if (! $response->successful()) {
                throw new CrmConnectionException("CRM API error: HTTP {$response->status()} on {$path}");
            }

            return (array) ($response->json() ?? []);
        } catch (ConnectionException $e) {
            throw new CrmConnectionException("CRM unreachable: {$e->getMessage()}", previous: $e);
        }
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken(base64_encode($this->apiKey . ':'))
            ->timeout(5)
            ->retry(2, 500, throw: false);
    }
}
