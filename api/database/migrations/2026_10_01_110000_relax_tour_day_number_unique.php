<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The admin renumbers days when they are dragged into a new order; a unique (tour_id, day_number)
 * would reject the intermediate state of a swap, so it becomes a plain index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_days', function (Blueprint $table) {
            $table->dropUnique(['tour_id', 'day_number']);
            $table->index(['tour_id', 'day_number']);
        });
    }

    public function down(): void
    {
        Schema::table('tour_days', function (Blueprint $table) {
            $table->dropIndex(['tour_id', 'day_number']);
            $table->unique(['tour_id', 'day_number']);
        });
    }
};
