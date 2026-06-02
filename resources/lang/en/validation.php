<?php

return [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute field must be a valid email address.',
    'string' => 'The :attribute field must be a string.',
    'max' => [
        'string' => 'The :attribute field must not be greater than :max characters.',
    ],
    'unique' => 'The :attribute has already been taken.',
    'in' => 'The selected :attribute is invalid.',

    'attributes' => [
        'email' => 'email',
        'password' => 'password',
        'name' => 'name',
        'code' => 'code',
        'description' => 'description',
        'status' => 'status',
        'password_confirmation' => 'password confirmation',
        'role' => 'role',
        'phone' => 'phone',
        'address' => 'address',
    ],
];
