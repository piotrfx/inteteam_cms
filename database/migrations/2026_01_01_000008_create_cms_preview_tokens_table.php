<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_preview_tokens', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->enum('content_type', ['page', 'post']);
            $table->ulid('content_id');
            $table->foreignUlid('revision_id')->constrained('cms_page_revisions')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('viewed_at')->nullable();
            $table->enum('created_by_type', ['user', 'ai_agent']);
            $table->timestamp('created_at')->nullable();

            $table->index('token', 'idx_preview_tokens_token');
            $table->index(['company_id', 'expires_at'], 'idx_preview_tokens_company');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_preview_tokens');
    }
};
