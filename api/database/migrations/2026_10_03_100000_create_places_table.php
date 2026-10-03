<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Points of interest for the maps (lakes, passes, canyons...), ported from the GO-Kyrgyzstan prototype.
 * A tour day can point to the place it ends at, so the tour map numbers its stops by day.
 * The drawn route itself lives in tours.route_geojson (see App\Services\Maps\RouteBuilder).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('kind')->index();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            $table->unsignedSmallInteger('altitude_m')->nullable();
            $table->text('summary')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::table('tour_days', function (Blueprint $table) {
            $table->foreignId('place_id')->nullable()->after('max_altitude_m')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tour_days', function (Blueprint $table) {
            $table->dropConstrainedForeignId('place_id');
        });
        Schema::dropIfExists('places');
    }
};
