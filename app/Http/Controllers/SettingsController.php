<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function show(): View
    {
        $user = [];
        $meResponse = $this->api->get('/v1/me', token: $this->token());
        if ($meResponse->successful()) {
            $json = $meResponse->json();
            if (is_array($json)) {
                $user = $this->flattenSingle($json);
                if ($user === [] || ! array_key_exists('username', $user)) {
                    $user = $this->normalizeUserFromApiResponse($json);
                }
                if ($user !== []) {
                    session()->put('user', $user);
                } else {
                    $dataKeys = isset($json['data']) && is_array($json['data'])
                        ? array_keys($json['data'])
                        : null;
                    Log::warning('Settings: /v1/me returned 200 but user could not be extracted', [
                        'response_keys' => array_keys($json),
                        'has_data' => isset($json['data']),
                        'data_keys' => $dataKeys,
                    ]);
                }
            } else {
                Log::warning('Settings: /v1/me response body is not an array', [
                    'body_preview' => substr($meResponse->body(), 0, 200),
                ]);
            }
        } else {
            Log::warning('Settings: /v1/me request failed', [
                'status' => $meResponse->status(),
                'body_preview' => substr($meResponse->body(), 0, 200),
            ]);
        }
        if ($user === []) {
            $user = session('user') ?? [];
        }
        if (! is_array($user)) {
            $user = [];
        }
        $user = $this->normalizeUserForView($user);

        $preferences = null;
        $response = $this->api->get('/v1/me/preferences', token: $this->token());
        if ($response->successful()) {
            $json = $response->json();
            if (is_array($json)) {
                $preferences = $this->flattenSingle($json);
            }
        }

        return view('settings.index', compact('user', 'preferences'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->api->patch('/v1/me/preferences', [
            'data' => [
                'type' => 'user-preferences',
                'attributes' => $request->only('spice-tolerance'),
            ],
        ], $this->token());

        return back()->with('success', 'Preferences updated.');
    }

    /**
     * Extract a flat user array from /v1/me response when it's not standard JSON:API or flatten missed attributes.
     */
    private function normalizeUserFromApiResponse(array $json): array
    {
        if (isset($json['data']) && is_array($json['data'])) {
            $data = $json['data'];
            $attrs = $data['attributes'] ?? [];
            if (is_array($attrs)) {
                return array_merge(
                    ['id' => $data['id'] ?? $attrs['id'] ?? '', 'type' => $data['type'] ?? 'users'],
                    $attrs
                );
            }
        }
        if (isset($json['user']) && is_array($json['user'])) {
            return $json['user'];
        }
        if (array_key_exists('username', $json)) {
            return $json;
        }

        return [];
    }

    /**
     * Ensure the view always gets a flat array with username, name, email, created_at, author (and author.*).
     */
    private function normalizeUserForView(array $user): array
    {
        if ($user === []) {
            return $user;
        }
        if (array_key_exists('username', $user)) {
            return $user;
        }
        if (isset($user['attributes']) && is_array($user['attributes'])) {
            return array_merge(
                ['id' => $user['id'] ?? '', 'type' => $user['type'] ?? ''],
                $user['attributes']
            );
        }

        return $user;
    }
}
