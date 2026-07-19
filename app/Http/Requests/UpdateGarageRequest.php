<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateGarageRequest extends StoreGarageRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = ['required', 'string', 'max:255', Rule::unique('garages', 'name')->ignore($this->route('garage'))];

        return $rules;
    }
}
