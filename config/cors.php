<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        'https://www.afhome.ph',
        'https://afhome.ph',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
],
'allowed_origins_patterns' => [
    '#^https://.*\.vercel\.app$#',
],
'supports_credentials' => true,

];
