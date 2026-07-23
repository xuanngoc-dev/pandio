<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Cho phép Vue SPA (Vite) gọi API Laravel từ domain/port khác.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Origin của frontend Vite (có thể thêm nhiều origin)
    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'http://localhost:5173'),
        'http://127.0.0.1:5173',
        'http://localhost:5173',
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Token Bearer không bắt buộc credentials cookie; để false cho đơn giản.
    // Nếu dùng cookie SPA Sanctum thì đổi thành true và cấu hình stateful domains.
    'supports_credentials' => false,

];
