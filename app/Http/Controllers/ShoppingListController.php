<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShoppingListController extends Controller
{
    public function index(): View
    {
        $response = $this->api->get('/v1/shopping-lists', token: $this->token());
        $lists = $response->successful()
            ? $this->flattenCollection($response->json())
            : [];

        return view('shopping-lists.index', compact('lists'));
    }

    public function show(string $id): View
    {
        $listResponse = $this->api->get("/v1/shopping-lists/{$id}", token: $this->token());
        $itemsResponse = $this->api->get("/v1/shopping-lists/{$id}/items", token: $this->token());

        $list = $listResponse->successful()
            ? $this->flattenSingle($listResponse->json())
            : null;

        $items = $itemsResponse->successful()
            ? $this->flattenCollection($itemsResponse->json())
            : [];

        return view('shopping-lists.show', compact('list', 'items'));
    }

    public function toggleItem(Request $request, string $listId, string $itemId): RedirectResponse
    {
        $checked = $request->boolean('checked');

        $this->api->patch("/v1/shopping-lists/{$listId}/items/{$itemId}", [
            'data' => [
                'type' => 'shopping-list-items',
                'id' => $itemId,
                'attributes' => ['checked' => ! $checked],
            ],
        ], $this->token());

        return back();
    }
}
