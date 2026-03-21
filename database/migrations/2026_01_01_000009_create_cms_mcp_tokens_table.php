<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_mcp_tokens', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name'); // e.g. "Claude.ai assistant"
            $table->string('token_hash', 64)->unique(); // SHA-256 of raw token
            $table->json('permissions')->default('["read"]'); // ["read","write","publish"]
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // null = no expiry
            $table->foreignUlid('created_by')->constrained('cms_users')->restrictOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->index(['company_id'], 'idx_mcp_tokens_company');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_mcp_tokens');
    }
};
