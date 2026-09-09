<?php

return [
    'admin_email' => env('ADMIN_EMAIL', 'admin@lozan-kw.com'),
    'admin_password' => env('ADMIN_PASSWORD'),
    'whatsapp' => env('ADMIN_WHATSAPP', ''),
    'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'telegram_webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    'supabase_url' => env('SUPABASE_URL'),
    'supabase_anon_key' => env('SUPABASE_ANON_KEY'),
    'shipping_fee' => 0,
    'handling_min_days' => 0,
    'handling_max_days' => 1,
    'transit_min_days' => 1,
    'transit_max_days' => 3,
    'return_days' => 7,
];
