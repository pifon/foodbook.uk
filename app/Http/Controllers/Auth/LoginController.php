<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $response = $this->api->post('/login', [
            'username' => $request->input('username'),
            'password' => $request->input('password'),
        ]);

        if ($response->failed()) {
            $detail = $response->json('errors.0.detail', 'Invalid credentials.');

            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => $detail]);
        }

        $token = $response->json('meta.access_token');
        $request->session()->put('api_token', $token);

        $userResponse = $this->api->get('/v1/me', token: $token);
        if ($userResponse->successful()) {
            $user = $this->flattenSingle($userResponse->json());
            $request->session()->put('user', $user);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(['api_token', 'user']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
