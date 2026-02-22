<?php

namespace App\Http\Controllers;

use App\Resources\Resources;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function __construct(
        private Resources $resources
        )
    {
    }

    /**
     * Get all available resources
     */
    public function index(): JsonResponse
    {
        return response()->json($this->resources->getAll());
    }

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

    /**
     * Get all resources for a specific handler
     */
    public function handlerIndex(string $handler): JsonResponse
    {
        $list = $this->resources->getByHandler($handler);

        if (empty($list)) {
            return response()->json([
                'error' => 'Handler not found or empty',
                'handler' => $handler,
            ], 404);
        }

        return response()->json($list);
    }
}