<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CmsNavigation;
use App\Models\CmsPage;
use App\Models\CmsPost;
use App\Models\CmsUser;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Dev company ──────────────────────────────────────────────────────
        $company = Company::create([
            'name' => 'Acme Repairs',
            'slug' => 'acme',
            'seo_site_name' => 'Acme Repairs',
            'seo_title_suffix' => '| Acme Repairs',
            'seo_meta_description' => 'Fast, reliable device repairs in your city.',
            'primary_colour' => '#6366f1',
            'theme' => 'default',
            'plan' => 'starter',
            'is_active' => true,
        ]);

        // ── Admin user ───────────────────────────────────────────────────────
        $admin = CmsUser::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Admin User',
            'email' => 'admin@acme.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // ── Editor user ──────────────────────────────────────────────────────
        CmsUser::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Editor User',
            'email' => 'editor@acme.test',
            'password' => Hash::make('password'),
            'role' => 'editor',
            'email_verified_at' => now(),
        ]);

        // ── Bind company so scoped creates work ──────────────────────────────
        app()->instance('current_company', $company);

        // ── Pages ────────────────────────────────────────────────────────────
        CmsPage::create([
            'company_id' => $company->id,
            'title' => 'Home',
            'slug' => 'home',
            'type' => 'home',
            'blocks' => [],
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        CmsPage::create([
            'company_id' => $company->id,
            'title' => 'About Us',
            'slug' => 'about',
            'type' => 'about',
            'blocks' => [
                [
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'type' => 'heading',
                    'data' => ['text' => 'About Acme Repairs', 'level' => 2],
                ],
                [
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'type' => 'rich_text',
                    'data' => ['content' => '<p>We\'ve been fixing devices since 2015. Come visit us!</p>'],
                ],
            ],
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        // ── Navigation ───────────────────────────────────────────────────────
        CmsNavigation::create([
            'company_id' => $company->id,
            'location' => 'header',
            'items' => [
                ['label' => 'Home', 'url' => '/', 'target' => '_self'],
                ['label' => 'About', 'url' => '/about', 'target' => '_self'],
                ['label' => 'Contact', 'url' => '/contact', 'target' => '_self'],
            ],
        ]);

        CmsNavigation::create([
            'company_id' => $company->id,
            'location' => 'footer',
            'items' => [
                ['label' => 'Privacy Policy', 'url' => '/privacy', 'target' => '_self'],
                ['label' => 'Terms', 'url' => '/terms', 'target' => '_self'],
            ],
        ]);

        // ── Second company (for isolation tests) ─────────────────────────────
        $company2 = Company::create([
            'name' => 'Beta Tech Fixes',
            'slug' => 'beta',
            'plan' => 'starter',
            'theme' => 'default',
            'is_active' => true,
        ]);

        CmsUser::withoutGlobalScopes()->create([
            'company_id' => $company2->id,
            'name' => 'Beta Admin',
            'email' => 'admin@beta.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }
}
