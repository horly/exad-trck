<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MobileUserResource;
use App\Models\ApplicationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileBootstrapController extends Controller
{
    public function __invoke(Request $request, ApplicationSetting $settings): JsonResponse
    {
        $user = $request->user()->loadMissing('fleet:id,name,code');

        return response()->json([
            'data' => [
                'api_version' => 'v1',
                'user' => (new MobileUserResource($user))->resolve(),
                'branding' => [
                    'app_name' => $settings->app_name,
                    'short_name' => $settings->short_name,
                    'logo_url' => $settings->logoUrl(),
                    'internal_logo_url' => $settings->internalLogoUrl(),
                    'favicon_url' => $settings->faviconUrl(),
                    'colors' => [
                        'primary' => $settings->primary_color,
                        'secondary' => $settings->secondary_color,
                        'button' => $settings->button_color,
                        'avatar' => $settings->avatar_color,
                        'accent' => $settings->accent_color,
                    ],
                    'support' => [
                        'email' => $settings->support_email,
                        'phone' => $settings->support_phone,
                    ],
                ],
            ],
        ]);
    }
}
