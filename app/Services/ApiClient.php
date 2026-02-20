<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ApiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly bool $verifySsl,
    ) {
    }

    public function get(string $path, array $query = [], ?string $token = null): Response
    {
        return $this->request($token)->get($this->url($path), $query);
    }

    public function post(string $path, array $data = [], ?string $token = null): Response
    {
        return $this->request($token)->post($this->url($path), $data);
    }

    public function patch(string $path, array $data = [], ?string $token = null): Response
    {
        return $this->request($token)->patch($this->url($path), $data);
    }

    public function delete(string $path, ?string $token = null): Response
    {
        return $this->request($token)->delete($this->url($path));
    }

    private function request(?string $token): PendingRequest
    {
        $request = Http::withHeaders([
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])->withOptions(['verify' => $this->verifySsl]);

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
