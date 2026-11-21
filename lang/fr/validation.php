<?php

/*
| Messages de validation en français (règles utilisées par l'application).
| Les noms de champs sont précisés dans chaque FormRequest via attributes().
*/

return [
    'accepted' => 'Le champ :attribute doit être accepté.',
    'array' => 'Le champ :attribute doit être une liste.',
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'date' => 'Le champ :attribute n’est pas une date valide.',
    'distinct' => 'Le champ :attribute contient une valeur en double.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'exists' => 'La valeur sélectionnée pour :attribute est invalide.',
    'file' => 'Le champ :attribute doit être un fichier.',
    'image' => 'Le champ :attribute doit être une image.',
    'in' => 'La valeur sélectionnée pour :attribute est invalide.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'lt' => [
        'numeric' => 'Le champ :attribute doit être inférieur à :value.',
    ],
    'max' => [
        'array' => 'Le champ :attribute ne peut pas contenir plus de :max éléments.',
        'file' => 'Le fichier :attribute ne doit pas dépasser :max Ko.',
        'numeric' => 'Le champ :attribute ne peut pas dépasser :max.',
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],
    'mimes' => 'Le champ :attribute doit être un fichier de type : :values.',
    'min' => [
        'array' => 'Le champ :attribute doit contenir au moins :min éléments.',
        'file' => 'Le fichier :attribute doit faire au moins :min Ko.',
        'numeric' => 'Le champ :attribute doit être au moins égal à :min.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'required_with' => 'Le champ :attribute est obligatoire quand :values est renseigné.',
    'string' => 'Le champ :attribute doit être un texte.',
    'unique' => 'Cette valeur de :attribute est déjà utilisée.',
    'uploaded' => 'Le téléversement du fichier :attribute a échoué.',

    'attributes' => [],
];
