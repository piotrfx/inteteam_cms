<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_page_revisions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->enum('content_type', ['page', 'post']);
            $table->ulid('content_id'); // no FK — intentionally polymorphic-free
            $table->json('blocks');
            $table->string('summary')->nullable();
            $table->enum('created_by_type', ['user', 'ai_agent']);
            $table->string('created_by_id')->nullable(); // cms_users.id or agent name
            $table->string('ai_session_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['content_type', 'content_id', 'created_at'], 'idx_revisions_content');
            $table->index(['company_id', 'created_at'], 'idx_revisions_company_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_page_revisions');
    }
};
