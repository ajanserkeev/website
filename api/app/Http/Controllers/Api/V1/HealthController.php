<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->select('select 1');
            $database = 'ok';
        } catch (Throwable) {
            $database = 'down';
        }

        $ok = $database === 'ok';

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'database' => $database,
        ], $ok ? 200 : 503);
    }
}
