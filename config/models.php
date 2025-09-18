<?php

return [

    '*' => [

        /*
        | Default path and namespace for generated models
        */
        'path' => app_path('Models'),
        'namespace' => 'App\Models',

        /*
        | Parent class for Eloquent models
        */
        'parent' => Illuminate\Database\Eloquent\Model::class,

        /*
        | Traits to use in all models
        */
        'use' => [
            // Ajoute tes traits ici si nécessaire
        ],

        /*
        | Connection to use for models
        */
        'connection' => 'pgsql',

        /*
        | Timestamps settings
        */
        'timestamps' => true,

        /*
        | Soft deletes
        */
        'soft_deletes' => true,

        /*
        | Date format
        */
        'date_format' => 'Y-m-d H:i:s',

        /*
        | Pagination default
        */
        'per_page' => 15,

        /*
        | Base files (non customizables)
        */
        'base_files' => false,

        /*
        | Snake attributes (snake_case vs camelCase)
        */
        'snake_attributes' => true,

        /*
        | Indentation in generated files
        */
        'indent_with_space' => 4,

        /*
        | Table name qualification
        */
        'qualified_tables' => false,

        /*
        | Hidden fields for models
        */
        'hidden' => [
            '*password', '*token', '*secret*'
        ],

        /*
        | Guarded attributes
        */
        'guarded' => [
            // tu peux mettre 'id', 'created_at', etc. si nécessaire
        ],

        /*
        | Casting
        */
        'casts' => [
            '*_json' => 'json',
        ],

        /*
        | Tables to exclude from generation
        */
        'except' => [
            'migrations',
            'failed_jobs',
            'password_resets',
            'personal_access_tokens',
            'password_reset_tokens',
        ],

        /*
        | Only generate these tables (si tu veux limiter)
        */
        'only' => [
            // laisse vide pour générer toutes les tables sauf celles dans except
        ],

        /*
        | Table prefix to remove
        */
        'table_prefix' => '',

        /*
        | Lower table name first before converting to StudlyCase
        */
        'lower_table_name_first' => false,

        /*
        | Pluralization for relations
        */
        'pluralize' => true,

        /*
        | Return type for relation methods
        */
        'enable_return_types' => false,
    ],

    /*
    | Connection specific overrides
    */
    'connections' => [
        'pgsql' => [
            // On peut ajouter des overrides spécifiques pour PostgreSQL si besoin
        ],
    ],

];
