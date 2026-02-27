<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|min:3',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $response = $this->api->postJsonApi('register', [
                'data' => [
                    'type' => 'users',
                    'attributes' => [
                        'name' => $request->input('name'),
                        'username' => $request->input('username'),
                        'email' => $request->input('email'),
                        'password' => $request->input('password'),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return back()
                ->withInput($request->only('name', 'username', 'email'))
                ->withErrors(['general' => 'Could not reach the registration service. Please try again.']);
        }

        if ($response->failed()) {
            $errors = [];
            $apiErrors = $response->json('errors', []);
            foreach ($apiErrors as $err) {
                $pointer = $err['source']['pointer'] ?? '';
                $field = $pointer !== '' ? last(explode('/', trim($pointer, '/'))) : 'general';
                $detail = $err['detail'] ?? $err['title'] ?? 'Validation error';
                $errors[$field] = $detail;
            }
            $errors['general'] = $this->apiErrorMessage($response);

            return back()
                ->withInput($request->only('name', 'username', 'email'))
                ->withErrors($errors);
        }

        $loginResponse = $this->api->post('/login', [
            'username' => $request->input('username'),
            'password' => $request->input('password'),
        ]);

        if ($loginResponse->failed()) {
            return redirect()->route('login')
                ->with('success', 'Account created. Please log in.');
        }

        $token = $loginResponse->json('meta.access_token');
        $request->session()->put('api_token', $token);

        $userResponse = $this->api->get('/v1/me', token: $token);
        if ($userResponse->successful()) {
            $user = $this->flattenSingle($userResponse->json());
            $request->session()->put('user', $user);
        }

        return redirect()->route('dashboard');
    }
}
