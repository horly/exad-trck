<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateProfileEmailRequest extends FormRequest
{
    protected $errorBag = 'updateEmail';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->user()?->id)],
            'email_current_password' => ['required', 'string', 'current_password:web'],
        ];
    }

    public function messages(): array
    {
        return ['email_current_password.current_password' => __('profile.current_password_invalid')];
    }
}
