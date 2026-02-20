<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function show(): View
    {
        $user = session('user');
        $response = $this->api->get('/v1/me/preferences', token: $this->token());
        $preferences = $response->successful()
            ? $this->flattenSingle($response->json())
            : null;

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
}
