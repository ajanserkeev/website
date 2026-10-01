<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalog: operators, tours and everything a tour page shows (docs/PLAN.md, data model).
 * Money is integer cents in USD. Dates of tours are plain dates in Asia/Bishkek.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->json('places')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('operators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('base_city')->nullable();
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->decimal('commission_rate', 5, 2);
            // Private: never exposed by the public API before deposit_paid.
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('tripadvisor_url')->nullable();
            $table->decimal('tripadvisor_rating', 2, 1)->nullable();
            $table->unsignedInteger('tripadvisor_reviews')->nullable();
            $table->string('google_url')->nullable();
            $table->decimal('google_rating', 2, 1)->nullable();
            $table->unsignedInteger('google_reviews')->nullable();
            $table->date('contract_signed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('operator_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('languages');
            $table->string('note')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->string('status')->default('draft')->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary');
            $table->text('description');
            $table->unsignedSmallInteger('duration_days');
            $table->unsignedTinyInteger('difficulty');
            $table->string('difficulty_note')->nullable();
            $table->unsignedSmallInteger('group_size_min')->default(1);
            $table->unsignedSmallInteger('group_size_max');
            $table->json('guide_languages');
            $table->string('route')->nullable();
            $table->string('start_point')->nullable();
            $table->string('end_point')->nullable();
            $table->json('route_geojson')->nullable();
            $table->unsignedSmallInteger('max_altitude_m')->nullable();
            $table->unsignedTinyInteger('season_from');
            $table->unsignedTinyInteger('season_to');
            $table->unsignedTinyInteger('min_age')->nullable();
            $table->json('highlights')->nullable();
            $table->boolean('has_group_dates')->default(false);
            $table->boolean('has_private_option')->default(false);
            // Overrides operators.commission_rate when set.
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->unsignedInteger('sort_weight')->default(0);
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tour_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('day_number');
            $table->string('title');
            $table->text('description');
            $table->string('overnight')->nullable();
            $table->json('meals')->nullable();
            $table->string('activity_hours')->nullable();
            $table->unsignedSmallInteger('max_altitude_m')->nullable();
            $table->timestamps();
            $table->unique(['tour_id', 'day_number']);
        });

        Schema::create('tour_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('text');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('tour_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('departures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedInteger('price_cents');
            // Null: children pay the adult price.
            $table->unsignedInteger('child_price_cents')->nullable();
            $table->unsignedSmallInteger('seats_total');
            $table->unsignedSmallInteger('seats_booked')->default(0);
            $table->string('status')->default('open');
            $table->timestamps();
            $table->index(['tour_id', 'starts_on']);
        });

        Schema::create('private_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('group_size_from');
            $table->unsignedSmallInteger('group_size_to');
            $table->unsignedInteger('price_per_person_cents');
            $table->unsignedInteger('child_price_cents')->nullable();
            // Months 1–12; null means the whole tour season.
            $table->unsignedTinyInteger('season_from')->nullable();
            $table->unsignedTinyInteger('season_to')->nullable();
            $table->timestamps();
        });

        Schema::create('region_tour', function (Blueprint $table) {
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->primary(['region_id', 'tour_id']);
        });

        Schema::create('activity_tour', function (Blueprint $table) {
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->primary(['activity_id', 'tour_id']);
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('intro')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamps();
        });

        Schema::create('collection_tour', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->primary(['collection_id', 'tour_id']);
        });
    }

    public function down(): void
    {
        foreach ([
            'collection_tour', 'collections', 'activity_tour', 'region_tour', 'private_prices', 'departures',
            'tour_faqs', 'tour_items', 'tour_days', 'tours', 'operator_guides', 'operators', 'activities', 'regions',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
