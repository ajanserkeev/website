<?php

namespace App\Filament\Resources\Tours\Pages\Concerns;

use App\Services\Maps\RouteBuilder;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

/**
 * Livewire calls of the route builder field ($wire.buildTourRoute / $wire.importTourGpx).
 * Errors come back as ['error' => message] so the map can show them without a page error.
 */
trait BuildsTourRoute
{
    public function buildTourRoute(array $waypoints, array $modes): array
    {
        $validator = Validator::make(compact('waypoints', 'modes'), [
            'waypoints' => ['required', 'array', 'min:2', 'max:60'],
            'waypoints.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'waypoints.*.lng' => ['required', 'numeric', 'between:-180,180'],
            'waypoints.*.name' => ['nullable', 'string', 'max:120'],
            'modes' => ['required', 'array', 'size:'.max(0, count($waypoints) - 1)],
            'modes.*' => ['required', 'in:'.implode(',', RouteBuilder::MODES)],
        ]);
        if ($validator->fails()) {
            return ['error' => $validator->errors()->first()];
        }

        try {
            return app(RouteBuilder::class)->build($waypoints, $modes);
        } catch (InvalidArgumentException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function importTourGpx(string $xml): array
    {
        if (strlen($xml) > 5 * 1024 * 1024) {
            return ['error' => 'GPX больше 5 МБ.'];
        }

        try {
            return app(RouteBuilder::class)->fromGpx($xml);
        } catch (InvalidArgumentException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
