<?php

return [
    'required' => 'Le champ :attribute est obligatoire.',
    'email' => 'Le champ :attribute doit être une adresse email valide.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'max' => [
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ],
    'unique' => 'La valeur du champ :attribute est déjà utilisée.',
    'in' => 'La valeur sélectionnée pour :attribute est invalide.',

    'attributes' => [
        'email' => 'adresse email',
        'password' => 'mot de passe',
        'name' => 'nom',
        'code' => 'code',
        'description' => 'description',
        'status' => 'statut',
        'password_confirmation' => 'confirmation du mot de passe',
        'role' => 'rôle',
        'phone' => 'téléphone',
        'address' => 'adresse',
    ],
];
