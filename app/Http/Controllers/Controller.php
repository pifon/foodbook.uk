<?php

namespace App\Http\Controllers;

use App\Services\ApiClient;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;

abstract class Controller
{
    /**
     * If the API returned 401 Unauthorized, clear session and return a 401 JSON response
     * so the frontend can redirect to login immediately.
     */
    protected function ifApiUnauthorized(Response $response): ?JsonResponse
    {
        if ($response->status() !== 401) {
            return null;
        }

        Session::forget(['api_token', 'user']);

        return response()->json([
            'error' => 'Session expired. Please log in again.',
            'redirect' => route('login'),
        ], 401);
    }
    /**
     * Build a user-facing error message from an API response (no generic fallbacks).
     */
    protected function apiErrorMessage(Response $response): string
    {
        $status = $response->status();
        $body = $response->body();

        $errors = $response->json('errors', []);
        if (! empty($errors)) {
            $messages = [];
            foreach ($errors as $err) {
                $detail = $err['detail'] ?? $err['title'] ?? null;
                if ($detail !== null && $detail !== '') {
                    $messages[] = $detail;
                }
            }
            if ($messages !== []) {
                return implode(' ', array_unique($messages));
            }
        }

        $message = $response->json('message') ?? $response->json('error');
        if (is_string($message) && $message !== '') {
            return $message;
        }

        if ($body !== '' && $body !== '0') {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                $msg = $decoded['message'] ?? $decoded['error'] ?? $decoded['detail'] ?? null;
                if (is_string($msg) && $msg !== '') {
                    return $msg;
                }
            }
            return strlen($body) > 500 ? substr($body, 0, 500) . '…' : $body;
        }

        return "HTTP {$status}.";
    }
    public function __construct(
        protected readonly ApiClient $api,
    ) {
    }

    protected function token(): ?string
    {
        return session('api_token');
    }

    /**
     * Flatten a JSON:API resource into a plain array.
     */
    protected function flatten(array $resource, array $included = []): array
    {
        if (! isset($resource['id'], $resource['type'])) {
            return [];
        }
        $result = ['id' => $resource['id'], 'type' => $resource['type']]
            + ($resource['attributes'] ?? []);

        foreach ($resource['relationships'] ?? [] as $key => $rel) {
            $data = $rel['data'] ?? null;
            if ($data === null) {
                $result[$key] = null;
                continue;
            }

            if (array_is_list($data)) {
                $result[$key] = array_map(
                    fn ($ref) => $this->resolveRef($ref, $included),
                    $data,
                );
            } else {
                $result[$key] = $this->resolveRef($data, $included);
            }
        }

        return $result;
    }

    protected function flattenCollection(array|null $document): array
    {
        if (! is_array($document)) {
            return [];
        }
        $included = $document['included'] ?? [];
        $data = $document['data'] ?? null;
        if (! is_array($data)) {
            return [];
        }

        return array_map(fn ($r) => $this->flatten($r, $included), $data);
    }

    protected function flattenSingle(array $document): array
    {
        $data = $document['data'] ?? null;
        if (! is_array($data) || ! isset($data['id'], $data['type'])) {
            return [];
        }
        return $this->flatten($data, $document['included'] ?? []);
    }

    private function resolveRef(array $ref, array $included): array
    {
        foreach ($included as $item) {
            if ($item['type'] === $ref['type'] && $item['id'] === $ref['id']) {
                return $this->flatten($item, $included);
            }
        }

        return ['id' => $ref['id'], 'type' => $ref['type']];
    }
}
