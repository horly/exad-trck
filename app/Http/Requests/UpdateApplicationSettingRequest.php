<?php

namespace App\Http\Requests;

use App\Models\ApplicationSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSuperadmin();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'app_name' => trim((string) $this->input('app_name')),
            'short_name' => trim((string) $this->input('short_name')),
            'website_url' => filled($this->input('website_url')) ? trim((string) $this->input('website_url')) : null,
            'support_email' => filled($this->input('support_email')) ? mb_strtolower(trim((string) $this->input('support_email'))) : null,
            'support_phone' => filled($this->input('support_phone')) ? trim((string) $this->input('support_phone')) : null,
        ]);
    }

    public function rules(): array
    {
        $color = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return [
            'app_name' => ['required', 'string', 'max:80'],
            'short_name' => ['required', 'string', 'max:40'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'map_provider' => ['required', 'string', Rule::in(ApplicationSetting::MAP_PROVIDERS)],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=120,min_height=40,max_width=2400,max_height=1200'],
            'internal_logo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=80,min_height=30,max_width=2400,max_height=1200'],
            'favicon' => ['nullable', 'file', 'mimes:png,webp,ico', 'max:1024'],
            'primary_color' => $color,
            'secondary_color' => $color,
            'button_color' => $color,
            'avatar_color' => $color,
            'accent_color' => $color,
            'sidebar_start_color' => $color,
            'sidebar_end_color' => $color,
            'support_email' => ['nullable', 'email:rfc', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:40'],
        ];
    }
}
