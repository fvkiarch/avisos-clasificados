<?php

declare(strict_types=1);

return [
    'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),

    'cors' => [
        'origins' => array_filter(array_map(
            'trim',
            explode(',', getenv('CORS_ORIGINS') ?: 'http://127.0.0.1:8080, http://localhost:8080'),
        )),
    ],

    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'avisos_clasificados',

        'profiles' => [
            'admin' => [
                'user' => getenv('DB_USER_ADMIN') ?: 'app_admin',
                'pass' => getenv('DB_PASS_ADMIN') ?: 'admin123',
            ],
            'publicador' => [
                'user' => getenv('DB_USER_PUBLISHER') ?: 'app_publicador',
                'pass' => getenv('DB_PASS_PUBLISHER') ?: 'publicador123',
            ],
            'visitante' => [
                'user' => getenv('DB_USER_VISITOR') ?: 'app_visitante',
                'pass' => getenv('DB_PASS_VISITOR') ?: 'visitante123',
            ],
        ],
    ],

    'jwt' => [
        'secret' => getenv('JWT_SECRET') ?: 'secreto-para-dev-09346859034ioasdfjgslkdfgj-ldfkglfd934',
        'ttl' => (int) (getenv('JWT_TTL') ?: 3600),
    ],
];
