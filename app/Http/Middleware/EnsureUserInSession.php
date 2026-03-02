<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserInSession
{
    /**
     * If the user has an api_token but session has no (or empty) user,
     * fetch /v1/me and store so settings and author checks work.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->session()->get('api_token');
        if (empty($token)) {
            return $next($request);
        }

        $user = $request->session()->get('user');
        if (is_array($user) && $user !== [] && array_key_exists('username', $user)) {
            return $next($request);
        }

        $baseUrl = rtrim(config('services.api.base_url'), '/');
        if ($baseUrl === '') {
            return $next($request);
        }

        $url = $baseUrl . '/v1/me';
        $response = Http::withHeaders([
            'Accept' => 'application/vnd.api+json',
            'Authorization' => 'Bearer ' . $token,
        ])
            ->withOptions(['verify' => config('services.api.verify_ssl')])
            ->withoutRedirecting()
            ->get($url);

        if ($response->status() === 401) {
            $request->session()->forget(['api_token', 'user']);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'error' => 'Session expired. Please log in again.',
                    'redirect' => route('login'),
                ], 401);
            }

            return redirect()->route('login')->with('error', 'Session expired. Please log in again.');
        }

        if ($response->successful()) {
            $json = $response->json();
            if (is_array($json)) {
                $user = $this->flattenSingle($json);
                if (($user === [] || ! array_key_exists('username', $user)) && isset($json['data']['attributes'])) {
                    $data = $json['data'];
                    $user = array_merge(
                        ['id' => $data['id'] ?? '', 'type' => $data['type'] ?? 'users'],
                        $data['attributes']
                    );
                }
                if ($user !== [] && array_key_exists('username', $user)) {
                    $request->session()->put('user', $user);
                }
            }
        }

        return $next($request);
    }

    private function flattenSingle(array $document): array
    {
        $data = $document['data'] ?? null;
        if (! is_array($data) || ! isset($data['id'], $data['type'])) {
            return [];
        }
        return $this->flatten($data, $document['included'] ?? []);
    }

    private function flatten(array $resource, array $included = []): array
    {
        if (! isset($resource['id'], $resource['type'])) {
            return [];
        }
        $result = ['id' => $resource['id'], 'type' => $resource['type']]
            + ($resource['attributes'] ?? []);

        foreach ($resource['relationships'] ?? [] as $key => $rel) {
            $refData = $rel['data'] ?? null;
            if ($refData === null) {
                $result[$key] = null;
                continue;
            }
            if (array_is_list($refData)) {
                $result[$key] = array_map(fn ($ref) => $this->resolveRef($ref, $included), $refData);
            } else {
                $result[$key] = $this->resolveRef($refData, $included);
            }
        }

        return $result;
    }

    private function resolveRef(array $ref, array $included): array
    {
        foreach ($included as $item) {
            $match = isset($item['type'], $item['id'])
                && $item['type'] === $ref['type']
                && (string) $item['id'] === (string) $ref['id'];
            if ($match) {
                return $this->flatten($item, $included);
            }
        }
        return ['id' => $ref['id'], 'type' => $ref['type']];
    }
}
