<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MobileVehicleTripsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::in(['today', 'yesterday', 'week', 'current_month', 'last_month', 'custom'])],
            'start_date' => ['nullable', 'required_if:period,custom', 'date'],
            'end_date' => ['nullable', 'required_if:period,custom', 'date'],
        ];
    }
}
