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

        try {
            $response = $this->api->postJsonApi('login', [
                'username' => $request->input('username'),
                'password' => $request->input('password'),
            ]);
        } catch (\Throwable $e) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => 'Request to API failed: ' . $e->getMessage()]);
        }

        if ($response->failed()) {
            $effectiveUri = $response->effectiveUri();
            $urlUsed = $effectiveUri !== null ? (string) $effectiveUri : (rtrim(config('services.api.base_url'), '/') . '/login');
            $body = $response->body();
            $message = sprintf(
                'Sent POST to %s. Response: HTTP %d. %s',
                $urlUsed,
                $response->status(),
                strlen($body) > 500 ? substr($body, 0, 500) . '…' : $body
            );
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => $message]);
        }

        $token = $response->json('meta.access_token') ?? $response->json('access_token');
        if (empty($token)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => 'API did not return an access token. Check API login response shape (meta.access_token or access_token).']);
        }

        try {
            $request->session()->put('api_token', $token);

            $userResponse = $this->api->get('/v1/me', token: $token);
            if ($userResponse->successful()) {
                $me = $userResponse->json();
                if (isset($me['data']) && is_array($me['data'])) {
                    $user = $this->flattenSingle($me);
                    $request->session()->put('user', $user);
                }
            }

            return redirect()->intended(route('dashboard'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Login post-success error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['login' => 'Login succeeded but failed to load user: ' . ($e->getMessage())]);
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(['api_token', 'user']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
