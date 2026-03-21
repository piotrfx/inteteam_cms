<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

final class BlockRendererService
{
    public function __construct(private readonly string $theme = 'default') {}

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
        $type = $block['type'] ?? 'unknown';
        $data = $block['data'] ?? [];

        $view = $this->resolveView($type);

        try {
            return view($view, ['data' => $data, 'block' => $block])->render();
        } catch (\Throwable $e) {
            Log::warning("BlockRendererService: failed to render block [{$type}]", [
                'error' => $e->getMessage(),
                'block_id' => $block['id'] ?? null,
            ]);

            // Graceful degradation — never break the public page
            return $this->renderFallback($type, $data);
        }
    }

    /**
     * Resolve the Blade view for a block type.
     * Falls back from active theme → default theme → generic fallback.
     */
    private function resolveView(string $type): string
    {
        $themeView   = "themes.{$this->theme}.blocks.{$type}";
        $defaultView = "themes.default.blocks.{$type}";

        if (View::exists($themeView)) {
            return $themeView;
        }

        if (View::exists($defaultView)) {
            return $defaultView;
        }

        return 'themes.default.blocks._unknown';
    }

    /** @param  array<string, mixed>  $data */
    private function renderFallback(string $type, array $data): string
    {
        // Only rendered in non-production for debugging; silently empty in production
        if (app()->isProduction()) {
            return '';
        }

        return sprintf(
            '<!-- Block [%s] render error: view not found or exception thrown -->',
            e($type),
        );
    }
}
