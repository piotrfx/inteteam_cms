<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_posts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUlid('author_id')->constrained('cms_users')->restrictOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('excerpt')->nullable();
            $table->json('blocks')->default('[]');
            $table->enum('status', ['draft', 'published', 'scheduled'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('featured_image_path')->nullable();

            // Revision pointers
            $table->foreignUlid('live_revision_id')->nullable()->constrained('cms_page_revisions')->nullOnDelete();
            $table->foreignUlid('staged_revision_id')->nullable()->constrained('cms_page_revisions')->nullOnDelete();

            // SEO overrides
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 160)->nullable();
            $table->string('seo_og_image_path')->nullable();
            $table->string('seo_canonical_url')->nullable();
            $table->enum('seo_robots', ['index,follow', 'noindex,nofollow'])->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'slug'], 'idx_posts_company_slug');
            $table->index(['company_id', 'status', 'published_at'], 'idx_posts_company_status_published');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_posts');
    }
};
