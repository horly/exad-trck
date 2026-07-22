<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class MobileLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_identifier' => ['required', 'string', 'max:128'],
            'device_name' => ['required', 'string', 'max:100'],
            'platform' => ['required', 'in:android,ios'],
            'app_version' => ['nullable', 'string', 'max:32'],
        ];
    }
}
