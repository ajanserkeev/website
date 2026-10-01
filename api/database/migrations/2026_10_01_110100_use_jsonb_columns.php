<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PostgreSQL cannot compare `json` values, so queries with DISTINCT (Filament tables with relationship
 * columns and filters) fail on tables that have json columns. `jsonb` supports equality and indexing.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'regions' => ['places'],
        'operator_guides' => ['languages'],
        'tours' => ['guide_languages', 'route_geojson', 'highlights'],
        'tour_days' => ['meals'],
        'inquiries' => ['answers', 'utm'],
        'bookings' => ['utm'],
        'payments' => ['raw'],
        'webhook_events' => ['payload'],
        'posts' => ['sections', 'facts'],
    ];

    public function up(): void
    {
        $this->convert('jsonb');
    }

    public function down(): void
    {
        $this->convert('json');
    }

    private function convert(string $type): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE {$type} USING {$column}::{$type}");
            }
        }
    }
};
