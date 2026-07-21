<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DisableProfileTwoFactorRequest extends FormRequest
{
    protected $errorBag = 'disableTwoFactorAuthentication';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['disable_current_password' => ['required', 'string', 'current_password:web']];
    }

    public function messages(): array
    {
        return ['disable_current_password.current_password' => __('profile.current_password_invalid')];
    }
}
