<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_media', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUlid('uploaded_by')->constrained('cms_users')->restrictOnDelete();
            $table->string('filename');
            $table->string('path');
            $table->enum('disk', ['local', 's3', 'gcs'])->default('local');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('caption')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'created_at'], 'idx_media_company_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_media');
    }
};
