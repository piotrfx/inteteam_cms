<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\CmsPreviewToken;
use App\Models\Company;
use App\Services\BlockRendererService;
use App\Services\PreviewTokenService;
use App\Services\SeoMetaService;
use Illuminate\Http\Response;

final class PreviewController extends Controller
{
    public function __construct(
        private readonly PreviewTokenService $previewTokenService,
        private readonly BlockRendererService $blockRenderer,
        private readonly SeoMetaService $seoMeta,
    ) {}

    public function __invoke(string $token): Response
    {
        $record = $this->previewTokenService->validate($token);

        [$content, $company, $seo, $renderedBlocks] = $this->resolveContent($record);

        $theme = $company->theme ?? 'default';

        $view = $content instanceof CmsPage
            ? "themes.{$theme}.page"
            : "themes.{$theme}.post";

        // @phpstan-ignore argument.type
        $html = view($view, [
            'company' => $company,
            ($content instanceof CmsPage ? 'page' : 'post') => $content,
            'renderedBlocks' => $renderedBlocks,
            'seo' => $seo,
            'nav' => [],
            'previewToken' => $token,
            'previewRevision' => $record->revision,
        ])->render();

        // Inject preview banner before </body>
        $banner = $this->renderBanner($token, $record);
        $html = str_replace('</body>', $banner . '</body>', $html);

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    /** @return array{0: CmsPage|CmsPost, 1: Company, 2: array<string,mixed>, 3: string} */
    private function resolveContent(CmsPreviewToken $record): array
    {
        if ($record->content_type === 'page') {
            /** @var CmsPage $content */
            $content = CmsPage::withoutGlobalScopes()
                ->with('company')
                ->findOrFail($record->content_id);

            /** @var Company $company */
            $company = $content->company;

            // Use staged blocks from the revision
            $content->blocks = $record->revision !== null ? ($record->revision->blocks ?? []) : [];

            $seo = $this->seoMeta->forPage($content, $company);
            $renderedBlocks = $this->blockRenderer->render($content->blocks);
        } else {
            /** @var CmsPost $content */
            $content = CmsPost::withoutGlobalScopes()
                ->with('company')
                ->findOrFail($record->content_id);

            /** @var Company $company */
            $company = $content->company;

            $content->blocks = $record->revision !== null ? ($record->revision->blocks ?? []) : [];

            $seo = $this->seoMeta->forPost($content, $company);
            $renderedBlocks = $this->blockRenderer->render($content->blocks);
        }

        return [$content, $company, $seo, $renderedBlocks];
    }

    private function renderBanner(string $token, CmsPreviewToken $record): string
    {
        $author = $record->revision?->created_by_type === 'ai_agent' ? 'AI Assistant' : 'Editor';
        $rawSummary = $record->revision !== null ? ($record->revision->summary ?? '') : '';
        $summary = htmlspecialchars($rawSummary, ENT_QUOTES);
        $csrfToken = csrf_token();
        $summaryHtml = $summary !== ''
            ? "<span style=\"opacity:.6;font-style:italic;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;\">{$summary}</span>"
            : '<span style="flex:1"></span>';

        return <<<HTML
<div id="cms-preview-banner" style="position:fixed;top:0;left:0;width:100%;z-index:9999;background:#1e1b4b;color:#e0e7ff;font-family:system-ui,sans-serif;font-size:14px;display:flex;align-items:center;gap:16px;padding:10px 20px;box-shadow:0 2px 8px rgba(0,0,0,0.4);">
  <span style="font-weight:600;">&#9889; Preview</span>
  <span style="opacity:.8">Changes by <strong>{$author}</strong></span>
  {$summaryHtml}
  <form method="POST" action="/preview/{$token}/publish" style="margin:0;">
    <input type="hidden" name="_token" value="{$csrfToken}">
    <button type="submit" style="background:#4f46e5;color:#fff;border:none;border-radius:6px;padding:6px 16px;font-size:13px;font-weight:600;cursor:pointer;">&#10003; Publish Live</button>
  </form>
  <form method="POST" action="/preview/{$token}/discard" style="margin:0;">
    <input type="hidden" name="_token" value="{$csrfToken}">
    <button type="submit" style="background:transparent;color:#a5b4fc;border:1px solid #4f46e5;border-radius:6px;padding:6px 14px;font-size:13px;cursor:pointer;">&#10005; Discard</button>
  </form>
</div>
<div style="height:48px;"></div>
HTML;
    }
}
