<?php

namespace App\Http\Controllers;

use App\Services\AddressSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __invoke(Request $request, AddressSearchService $addressSearch): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:3', 'max:180'],
        ]);

        return response()->json(['results' => $addressSearch->search($validated['query'])]);
    }
}
