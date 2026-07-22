<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MobileUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileProfileController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('fleet:id,name,code');

        return response()->json([
            'data' => (new MobileUserResource($user))->resolve(),
        ]);
    }
}
