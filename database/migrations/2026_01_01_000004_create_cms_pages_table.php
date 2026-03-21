<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_pages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->enum('type', ['home', 'about', 'contact', 'privacy', 'terms', 'custom'])->default('custom');
            $table->json('blocks')->default('[]');
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();

            // Revision pointers
            $table->foreignUlid('live_revision_id')->nullable()->constrained('cms_page_revisions')->nullOnDelete();
            $table->foreignUlid('staged_revision_id')->nullable()->constrained('cms_page_revisions')->nullOnDelete();

            // SEO overrides
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 160)->nullable();
            $table->string('seo_og_image_path')->nullable();
            $table->string('seo_canonical_url')->nullable();
            $table->enum('seo_robots', ['index,follow', 'noindex,nofollow'])->nullable();
            $table->enum('seo_schema_type', ['WebPage', 'FAQPage', 'ContactPage'])->default('WebPage');

            $table->foreignUlid('created_by')->nullable()->constrained('cms_users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'slug'], 'idx_pages_company_slug');
            $table->index(['company_id', 'status'], 'idx_pages_company_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_pages');
    }
};
