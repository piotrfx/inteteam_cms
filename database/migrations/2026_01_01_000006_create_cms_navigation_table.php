<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_navigation', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->enum('location', ['header', 'footer']);
            $table->json('items')->default('[]'); // [{label, url, target, children:[...]}]
            $table->timestamps();

            $table->unique(['company_id', 'location'], 'idx_nav_company_location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_navigation');
    }
};
