<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRecoveryCodesRequest extends FormRequest
{
    protected $errorBag = 'recoveryCodes';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['recovery_current_password' => ['required', 'string', 'current_password:web']];
    }

    public function messages(): array
    {
        return ['recovery_current_password.current_password' => __('profile.current_password_invalid')];
    }
}
