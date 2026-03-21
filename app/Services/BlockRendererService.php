<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CrmConnectionException;
use App\Exceptions\NoCrmConnectionException;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

final class BlockRendererService
{
    /** @var array<string> CRM block types that require live API data */
    private const CRM_BLOCK_TYPES = ['gallery', 'storefront', 'crm_form', 'business_updates'];

    public function __construct(
        private readonly string $theme = 'default',
        private readonly ?Company $company = null,
        private readonly ?CrmApiClientFactory $crmFactory = null,
    ) {}

    /**
     * Render an array of blocks to an HTML string.
     *
     * @param  array<int, array{id: string, type: string, data: array<string, mixed>}>  $blocks
     */
    public function render(array $blocks): string
    {
        $html = '';

        foreach ($blocks as $block) {
            $html .= $this->renderBlock($block);
        }

        return $html;
    }

    /** @param  array{id: string, type: string, data: array<string, mixed>}  $block */
    private function renderBlock(array $block): string
    {
        $type = $block['type'];
        $data = $block['data'];

        // Enrich CRM blocks with live data
        if (in_array($type, self::CRM_BLOCK_TYPES, true)) {
            return $this->renderCrmBlock($type, $data, $block);
        }

        $view = $this->resolveView($type);

        try {
            return view($view, ['data' => $data, 'block' => $block])->render();
        } catch (\Throwable $e) {
            Log::warning("BlockRendererService: failed to render block [{$type}]", [
                'error' => $e->getMessage(),
                'block_id' => $block['id'],
            ]);

            return $this->renderFallback($type, $data);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{id: string, type: string, data: array<string, mixed>}  $block
     */
    private function renderCrmBlock(string $type, array $data, array $block): string
    {
        try {
            if ($this->crmFactory === null || $this->company === null) {
                throw new NoCrmConnectionException;
            }

            $client = $this->crmFactory->forCompany($this->company);
            $crmData = $this->fetchCrmData($client, $type, $data);

            $view = $this->resolveView($type);

            return view($view, ['data' => $data, 'crmData' => $crmData, 'block' => $block])->render();
        } catch (NoCrmConnectionException $e) {
            Log::debug("BlockRendererService: CRM not connected for block [{$type}]");
        } catch (CrmConnectionException $e) {
            Log::warning("BlockRendererService: CRM error for block [{$type}]", ['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error("BlockRendererService: unexpected error for CRM block [{$type}]", ['error' => $e->getMessage()]);
        }

        // Render error partial for all CRM failures
        return $this->renderCrmError($type);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function fetchCrmData(CrmApiClient $client, string $type, array $data): array
    {
        $gallerySlug  = is_string($data['gallery_slug']  ?? null) ? $data['gallery_slug']  : '';
        $categorySlug = is_string($data['category_slug'] ?? null) ? $data['category_slug'] : null;
        $formSlug     = is_string($data['form_slug']     ?? null) ? $data['form_slug']     : '';
        $limit        = is_int($data['limit'] ?? null) ? $data['limit'] : 5;

        return match ($type) {
            'gallery'          => $client->gallery($gallerySlug),
            'storefront'       => [
                'products'   => $client->products($categorySlug, is_int($data['limit'] ?? null) ? $data['limit'] : 12),
                'categories' => $client->productCategories(),
                'config'     => $client->storefrontConfig(),
            ],
            'crm_form'         => $client->formSchema($formSlug),
            'business_updates' => $client->businessUpdates($limit),
            default            => [],
        };
    }

    private function renderCrmError(string $type): string
    {
        $errorView = "themes.{$this->theme}.blocks.{$type}_error";
        $fallback = "themes.default.blocks.{$type}_error";

        if (View::exists($errorView)) {
            /** @phpstan-ignore argument.type */
            return view($errorView)->render();
        }

        if (View::exists($fallback)) {
            /** @phpstan-ignore argument.type */
            return view($fallback)->render();
        }

        return sprintf('<div class="cms-block-error" data-type="%s" style="padding:1rem;background:#fef2f2;color:#ef4444;border-radius:8px;font-size:.875rem;">%s unavailable.</div>', e($type), e(ucwords(str_replace('_', ' ', $type))));
    }

    /**
     * Resolve the Blade view for a block type.
     * Falls back from active theme → default theme → generic fallback.
     *
     * @return view-string
     */
    private function resolveView(string $type): string
    {
        $themeView = "themes.{$this->theme}.blocks.{$type}";
        $defaultView = "themes.default.blocks.{$type}";

        if (View::exists($themeView)) {
            /** @phpstan-ignore return.type */
            return $themeView;
        }

        if (View::exists($defaultView)) {
            /** @phpstan-ignore return.type */
            return $defaultView;
        }

        return 'themes.default.blocks._unknown';
    }

    /** @param  array<string, mixed>  $data */
    private function renderFallback(string $type, array $data): string
    {
        if (app()->isProduction()) {
            return '';
        }

        return sprintf(
            '<!-- Block [%s] render error: view not found or exception thrown -->',
            e($type),
        );
    }
}
