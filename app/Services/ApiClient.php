<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly bool $verifySsl,
    ) {
        if (trim($baseUrl) === '') {
            throw new \InvalidArgumentException(
                'API_BASE_URL must be set in .env (API = external backend; api = internal Foodbook proxy).'
            );
        }
    }

    private const LOG_RESPONSE_MAX_BYTES = 8000;

    private function shouldLogFrames(): bool
    {
        return Config::get('app.debug', false);
    }

    public function get(string $path, array $query = [], ?string $token = null): Response
    {
        $url = $this->url($path);
        if ($this->shouldLogFrames()) {
            Log::debug('API request', [
                'method' => 'GET',
                'url' => $url,
                'query' => $query,
            ]);
        }
        $response = $this->request($token)->get($url, $query);
        if ($this->shouldLogFrames()) {
            $this->logResponse('GET', $url, $response);
        }
        return $response;
    }

    public function post(string $path, array $data = [], ?string $token = null): Response
    {
        $url = $this->url($path);
        if ($this->shouldLogFrames()) {
            Log::debug('API request', [
                'method' => 'POST',
                'url' => $url,
                'payload' => $data,
            ]);
        }
        $response = $this->request($token)->post($url, $data);
        if ($this->shouldLogFrames()) {
            $this->logResponse('POST', $url, $response);
        }
        return $response;
    }

    /**
     * POST with JSON:API body (application/vnd.api+json).
     * Uses raw body to ensure method and body are not altered by redirects.
     */
    public function postJsonApi(string $path, array $data, ?string $token = null): Response
    {
        $url = $this->url($path);
        $body = json_encode($data);
        if ($this->shouldLogFrames()) {
            Log::debug('API request', [
                'method' => 'POST',
                'url' => $url,
                'payload' => $data,
            ]);
        }
        $pending = Http::withHeaders([
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])
            ->withBody($body, 'application/vnd.api+json')
            ->withOptions(['verify' => $this->verifySsl])
            ->withoutRedirecting();

        if ($token) {
            $pending = $pending->withToken($token);
        }

        $response = $pending->post($url);
        if ($this->shouldLogFrames()) {
            $this->logResponse('POST', $url, $response);
        }
        return $response;
    }

    /**
     * POST as application/json (e.g. for auth endpoints that expect plain JSON).
     */
    public function postAsJson(string $path, array $data): Response
    {
        $base = trim($this->baseUrl);
        $url = rtrim($base, '/') . '/' . ltrim($path, '/');

        $host = parse_url($url, PHP_URL_HOST);
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === null) {
            throw new \InvalidArgumentException(
                'API_BASE_URL must point to the remote API server, not to this app. Do not use 127.0.0.1 or localhost.'
            );
        }

        if ($this->shouldLogFrames()) {
            Log::info('API request', [
                'method' => 'POST',
                'url' => $url,
                'payload' => $data,
            ]);
        }
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
            ->withOptions(['verify' => $this->verifySsl])
            ->withoutRedirecting()
            ->post($url, $data);
        if ($this->shouldLogFrames()) {
            $this->logResponse('POST', $url, $response);
        }
        return $response;
    }

    /**
     * POST JSON (application/json) with optional Bearer token. For endpoints like directions/from-text.
     */
    public function postJson(string $path, array $data, ?string $token = null): Response
    {
        $url = $this->url($path);
        $pending = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->withOptions(['verify' => $this->verifySsl])->withoutRedirecting();
        if ($token !== null && $token !== '') {
            $pending = $pending->withToken($token);
        }
        if ($this->shouldLogFrames()) {
            Log::debug('API request', ['method' => 'POST', 'url' => $url, 'payload' => $data]);
        }
        $response = $pending->post($url, $data);
        if ($this->shouldLogFrames()) {
            $this->logResponse('POST', $url, $response);
        }
        return $response;
    }

    public function patch(string $path, array $data = [], ?string $token = null): Response
    {
        $url = $this->url($path);
        if ($this->shouldLogFrames()) {
            Log::debug('API request', [
                'method' => 'PATCH',
                'url' => $url,
                'payload' => $data,
            ]);
        }
        $response = $this->request($token)->patch($url, $data);
        if ($this->shouldLogFrames()) {
            $this->logResponse('PATCH', $url, $response);
        }
        return $response;
    }

    public function delete(string $path, ?string $token = null): Response
    {
        $url = $this->url($path);
        if ($this->shouldLogFrames()) {
            Log::debug('API request', [
                'method' => 'DELETE',
                'url' => $url,
            ]);
        }
        $response = $this->request($token)->delete($url);
        if ($this->shouldLogFrames()) {
            $this->logResponse('DELETE', $url, $response);
        }
        return $response;
    }

    private function logResponse(string $method, string $url, Response $response): void
    {
        $body = $response->body();
        $truncated = strlen($body) > self::LOG_RESPONSE_MAX_BYTES
            ? substr($body, 0, self::LOG_RESPONSE_MAX_BYTES) . '… [truncated]'
            : $body;
        Log::debug('API response', [
            'method' => $method,
            'url' => $url,
            'status' => $response->status(),
            'body' => $truncated,
        ]);

        if ($this->shouldLogFrames() && app()->bound('request')) {
            $request = request();
            $log = $request->attributes->get('api_log', []);
            $log[] = [
                'method' => $method,
                'url' => $url,
                'status' => $response->status(),
                'message' => $this->responseStatusText($response),
                'detail' => $response->successful() ? null : $this->extractErrorFromBody($body),
            ];
            $request->attributes->set('api_log', $log);
        }
    }

    private function responseStatusText(Response $response): string
    {
        $reason = $response->reason();

        return $reason !== '' ? $reason : 'HTTP ' . $response->status();
    }

    private function extractErrorFromBody(string $body): ?string
    {
        $data = json_decode($body, true);
        if (isset($data['errors'][0]['detail'])) {
            return $data['errors'][0]['detail'];
        }
        if (isset($data['error'])) {
            return is_string($data['error']) ? $data['error'] : json_encode($data['error']);
        }
        if (isset($data['message'])) {
            return $data['message'];
        }

        return null;
    }

    private function request(?string $token): PendingRequest
    {
        $request = Http::withHeaders([
            'Accept' => 'application/vnd.api+json',
        ])
            ->bodyFormat('json')
            ->contentType('application/vnd.api+json')
            ->withOptions(['verify' => $this->verifySsl])
            ->withoutRedirecting();

        if ($token) {
            $request = $request->withToken($token);
        }

        return $request;
    }

    private function url(string $path): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');
    }
}
