<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained()->restrictOnDelete();
            // One review per booking; null for imported reviews.
            $table->foreignId('booking_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('source')->default('site');
            $table->string('author_name');
            $table->string('country', 60)->nullable();
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('body');
            // YYYY-MM
            $table->char('trip_month', 7)->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt');
            // Sections as JSON: [{id, title, paragraphs[], bullets[]}], mirrors the guide page layout.
            $table->json('sections');
            $table->json('facts')->nullable();
            $table->unsignedSmallInteger('reading_minutes')->default(5);
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('post_tour', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->primary(['post_id', 'tour_id']);
        });

        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->char('code', 3)->unique();
            $table->decimal('rate_per_usd', 12, 6);
            $table->timestamp('fetched_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['currency_rates', 'post_tour', 'posts', 'reviews'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
