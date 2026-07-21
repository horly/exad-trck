<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnableProfileTwoFactorRequest extends FormRequest
{
    protected $errorBag = 'enableTwoFactorAuthentication';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['enable_current_password' => ['required', 'string', 'current_password:web']];
    }

    public function messages(): array
    {
        return ['enable_current_password.current_password' => __('profile.current_password_invalid')];
    }
}
