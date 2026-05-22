<?php

return [
    'accepted' => 'Le champ :attribute doit être accepté.',
    'confirmed' => 'La confirmation du :attribute ne correspond pas.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'enum' => 'La valeur sélectionnée pour :attribute est invalide.',
    'max' => [
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
    ],
    'min' => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'password' => [
        'letters' => 'Le :attribute doit contenir au moins une lettre.',
        'mixed' => 'Le :attribute doit contenir au moins une majuscule et une minuscule.',
        'numbers' => 'Le :attribute doit contenir au moins un chiffre.',
        'symbols' => 'Le :attribute doit contenir au moins un symbole.',
        'uncompromised' => 'Le :attribute est apparu dans une fuite de données. Veuillez en choisir un autre.',
    ],
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'unique' => 'Cette adresse e-mail est déjà utilisée.',

    'attributes' => [
        'email' => 'adresse e-mail',
        'password' => 'mot de passe',
        'type' => 'type de compte',
        'model.first_name' => 'prénom',
        'model.last_name' => 'nom de famille',
        'model.name' => 'nom de l\'organisation',
    ],
];
