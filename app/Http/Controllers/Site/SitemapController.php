<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\CmsPost;
use Illuminate\Http\Response;

final class SitemapController extends Controller
{
    public function index(): Response
    {
        $company = app('current_company');

        $pages = CmsPage::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', 'published')
            ->get(['slug', 'updated_at']);

        $posts = CmsPost::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', 'published')
            ->get(['slug', 'updated_at']);

        $xml = view('sitemap', compact('pages', 'posts'))->render();

        return response($xml, 200)
            ->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $company  = app('current_company');
        $sitemapUrl = url('/sitemap.xml');

        $content = "User-agent: *\n";

        if (($company->seo_robots ?? '') === 'noindex') {
            $content .= "Disallow: /\n";
        } else {
            $content .= "Disallow: /admin/\n";
            $content .= "Sitemap: {$sitemapUrl}\n";
        }

        return response($content, 200)
            ->header('Content-Type', 'text/plain');
    }
}
