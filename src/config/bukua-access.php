<?php

return [
    'bukua_access' => [
        'client_id'     => env('BUKUA_ACCESS_CLIENT_ID') ?? env('BUKUA_CORE_ACCESS_CLIENT_ID') ?? null,
        'client_secret' => env('BUKUA_ACCESS_CLIENT_SECRET') ?? env('BUKUA_CORE_ACCESS_CLIENT_SECRET') ?? null,
        'base_url'      => env('BUKUA_BASE_URL', 'https://bukua-core.apptempest.com'),
    ],
];
