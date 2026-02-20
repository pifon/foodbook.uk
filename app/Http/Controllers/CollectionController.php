<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CollectionController extends Controller
{
    public function index(): View
    {
        $response = $this->api->get('/v1/collections', token: $this->token());
        $collections = $response->successful()
            ? $this->flattenCollection($response->json())
            : [];

        return view('collections.index', compact('collections'));
    }

    public function show(string $id): View
    {
        $response = $this->api->get("/v1/collections/{$id}", token: $this->token());

        $collection = null;
        if ($response->successful()) {
            $collection = $this->flattenSingle($response->json());
        }

        return view('collections.show', compact('collection'));
    }
}
