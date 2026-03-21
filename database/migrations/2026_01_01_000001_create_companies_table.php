<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();

            // CRM connection
            $table->string('crm_base_url')->nullable();
            $table->string('crm_company_id')->nullable();
            $table->string('crm_api_key')->nullable(); // encrypted at application layer

            // SEO — site-level defaults
            $table->string('seo_site_name')->nullable();
            $table->string('seo_title_suffix')->nullable();
            $table->string('seo_meta_description', 160)->nullable();
            $table->string('seo_og_image_path')->nullable();
            $table->string('seo_twitter_handle')->nullable();
            $table->string('seo_google_verification')->nullable();
            $table->enum('seo_robots', ['index,follow', 'noindex,nofollow'])->default('index,follow');

            // Local business (JSON-LD)
            $table->string('seo_address_street')->nullable();
            $table->string('seo_address_city')->nullable();
            $table->string('seo_address_postcode')->nullable();
            $table->string('seo_phone')->nullable();
            $table->json('seo_opening_hours')->nullable();
            $table->enum('seo_price_range', ['£', '££', '£££'])->nullable();

            // Branding
            $table->string('primary_colour', 7)->nullable(); // hex
            $table->string('theme')->default('default');
            $table->json('settings')->nullable();

            // Subscription tier
            $table->enum('plan', ['starter', 'standard', 'pro', 'enterprise'])->default('starter');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
