<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role->value,
            'profile_photo_url' => $this->profilePhotoUrl(),
            'fleet' => $this->fleet ? [
                'id' => $this->fleet->id,
                'name' => $this->fleet->name,
                'code' => $this->fleet->code,
            ] : null,
            'permissions' => [
                'map_view' => $this->hasClientPermission(User::PERMISSION_MAP_VIEW),
                'reports_generate' => $this->hasClientPermission(User::PERMISSION_REPORTS_GENERATE),
                'garages_manage' => $this->hasClientPermission(User::PERMISSION_GARAGES_MANAGE),
                'maintenance_manage' => $this->hasClientPermission(User::PERMISSION_MAINTENANCE_MANAGE),
            ],
            'two_factor_enabled' => $this->hasEnabledTwoFactorAuthentication(),
        ];
    }
}
