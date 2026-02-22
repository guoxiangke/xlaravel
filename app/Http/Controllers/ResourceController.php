<?php

namespace App\Http\Controllers;

use App\Resources\Resources;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function __construct(
        private Resources $resources
    ) {}

    /**
     * Handle resource requests
     */
    public function show(Request $request, string $keyword): JsonResponse
    {
        $resource = $this->resources->resolve($keyword);

        if ($resource === null) {
            return response()->json([
                'error' => 'Resource not found',
                'keyword' => $keyword,
            ], 404);
        }

        return response()->json($resource);
    }
}
