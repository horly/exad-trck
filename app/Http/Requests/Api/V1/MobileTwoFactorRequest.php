<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class MobileTwoFactorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'challenge_token' => ['required', 'string', 'max:128'],
            'code' => ['nullable', 'required_without:recovery_code', 'string', 'size:6'],
            'recovery_code' => ['nullable', 'required_without:code', 'string', 'max:100'],
        ];
    }
}
