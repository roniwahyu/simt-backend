<?php

return [

    'app_name' => env('SIMT_APP_NAME', 'SIMT MTs'),
    'app_version' => env('SIMT_APP_VERSION', '1.0.0'),

    'passing_grade' => env('SIMT_PASSING_GRADE', 75),

    'max_upload_size' => env('SIMT_MAX_UPLOAD_SIZE', 10240),

    'dapodik' => [
        'api_url' => env('DAPODIK_API_URL', 'https://dapo.kemdikbud.go.id/api'),
        'api_token' => env('DAPODIK_API_TOKEN', ''),
        'npsn' => env('DAPODIK_NPSN', ''),
        'enabled' => env('DAPODIK_ENABLED', false),
        'sync_interval' => env('DAPODIK_SYNC_INTERVAL', 86400),
    ],

    'emis' => [
        'api_url' => env('EMIS_API_URL', 'https://emis.kemenag.go.id/api'),
        'api_token' => env('EMIS_API_TOKEN', ''),
        'nism' => env('EMIS_NISM', ''),
        'enabled' => env('EMIS_ENABLED', false),
        'sync_interval' => env('EMIS_SYNC_INTERVAL', 86400),
    ],

    'payment' => [
        'gateway' => env('PAYMENT_GATEWAY', 'midtrans'),
        'spp_default_amount' => env('SPP_DEFAULT_AMOUNT', 150000),
        'payment_timeout' => env('PAYMENT_TIMEOUT', 86400),
        'auto_remind_days' => env('PAYMENT_AUTO_REMIND_DAYS', 3),

        'midtrans' => [
            'server_key' => env('MIDTRANS_SERVER_KEY', ''),
            'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
            'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
            'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),
            'is_3ds' => env('MIDTRANS_IS_3DS', true),
            'merchant_id' => env('MIDTRANS_MERCHANT_ID', ''),
            'callback_url' => env('MIDTRANS_CALLBACK_URL', ''),
        ],

        'xendit' => [
            'secret_key' => env('XENDIT_SECRET_KEY', ''),
            'public_key' => env('XENDIT_PUBLIC_KEY', ''),
            'callback_token' => env('XENDIT_CALLBACK_TOKEN', ''),
            'is_production' => env('XENDIT_IS_PRODUCTION', false),
            'callback_url' => env('XENDIT_CALLBACK_URL', ''),
        ],
    ],

    'parent_portal' => [
        'enabled' => env('PARENT_PORTAL_ENABLED', true),
        'nextjs_url' => env('NEXTJS_PARENT_PORTAL_URL', 'http://localhost:3000'),
        'registration_enabled' => env('PARENT_REGISTRATION_ENABLED', true),
    ],

    'report' => [
        'rapor_template' => env('RAPOR_TEMPLATE', 'default'),
        'header_image' => env('REPORT_HEADER_IMAGE', ''),
        'footer_text' => env('REPORT_FOOTER_TEXT', ''),
        'paper_size' => env('REPORT_PAPER_SIZE', 'a4'),
        'orientation' => env('REPORT_ORIENTATION', 'portrait'),
    ],

    'backup' => [
        'enabled' => env('BACKUP_ENABLED', true),
        'interval' => env('BACKUP_INTERVAL', 86400),
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
        'disk' => env('BACKUP_DISK', 'local'),
    ],
];
