<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_users', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('password')->nullable(); // nullable for Phase 3 SSO users
            $table->enum('role', ['admin', 'editor', 'viewer'])->default('editor');
            $table->string('sso_user_id')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'email']);
            $table->index(['company_id', 'role'], 'idx_cms_users_company_role');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('cms_users');
    }
};
