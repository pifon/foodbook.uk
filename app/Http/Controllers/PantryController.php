<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PantryController extends Controller
{
    public function __invoke(): View
    {
        $response = $this->api->get('/v1/pantry', token: $this->token());
        $items = $response->successful()
            ? $this->flattenCollection($response->json())
            : [];

        return view('pantry.index', compact('items'));
    }
}
