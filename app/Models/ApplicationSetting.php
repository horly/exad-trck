<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ApplicationSetting extends Model
{
    public const DEFAULT_COLORS = [
        'primary_color' => '#171064',
        'secondary_color' => '#2F67E8',
        'button_color' => '#171064',
        'avatar_color' => '#1D4ED8',
        'accent_color' => '#6D3DF2',
        'sidebar_start_color' => '#1B146F',
        'sidebar_end_color' => '#0F0A43',
    ];

    protected $fillable = [
        'app_name',
        'short_name',
        'website_url',
        'logo_path',
        'internal_logo_path',
        'favicon_path',
        'primary_color',
        'secondary_color',
        'button_color',
        'avatar_color',
        'accent_color',
        'sidebar_start_color',
        'sidebar_end_color',
        'support_email',
        'support_phone',
    ];

    protected $attributes = [
        'app_name' => 'EXAD Tracking',
        'short_name' => 'EXAD Tracking',
        'primary_color' => '#171064',
        'secondary_color' => '#2F67E8',
        'button_color' => '#171064',
        'avatar_color' => '#1D4ED8',
        'accent_color' => '#6D3DF2',
        'sidebar_start_color' => '#1B146F',
        'sidebar_end_color' => '#0F0A43',
    ];

    public function logoUrl(bool $forDarkSurface = false): string
    {
        if ($this->logo_path) {
            return Storage::disk('public')->url($this->logo_path);
        }

        return asset($forDarkSurface ? 'images/exad-cropped-white.png' : 'images/logo-exad-cropped.png');
    }

    public function faviconUrl(): string
    {
        return $this->favicon_path
            ? Storage::disk('public')->url($this->favicon_path)
            : asset('images/icon-exad-tracking.png');
    }

    public function internalLogoUrl(): string
    {
        return $this->internal_logo_path
            ? Storage::disk('public')->url($this->internal_logo_path)
            : $this->logoUrl(true);
    }
}
