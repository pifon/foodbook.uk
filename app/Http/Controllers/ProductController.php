<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    /**
     * Create a product via API: POST /v1/products (JSON:API).
     * See api/Docs/FRONTEND-AGENT-PROMPT-CREATE-PRODUCT.md.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $attrs = [
            'name' => $request->input('name'),
            'description' => $request->input('description') ?? '',
        ];
        if ($request->filled('slug')) {
            $attrs['slug'] = $request->input('slug');
        }

        try {
            $response = $this->api->postJsonApi(
                '/v1/products',
                [
                    'data' => [
                        'type' => 'products',
                        'attributes' => $attrs,
                    ],
                ],
                $this->token()
            );
        } catch (\Throwable $e) {
            Log::error('ProductController::store API call failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to create product.',
                'message' => config('app.debug') ? $e->getMessage() : null,
            ], 502);
        }

        if ($unauth = $this->ifApiUnauthorized($response)) {
            return $unauth;
        }

        try {
            $body = $response->json();
        } catch (\Throwable $e) {
            $body = null;
        }
        $body = is_array($body) ? $body : ['error' => $response->body() ?: 'Invalid response from API'];

        if (! $response->successful()) {
            $status = $response->status() >= 400 ? $response->status() : 422;

            return response()->json($body, $status);
        }

        return response()->json($body, 201);
    }
}
