<?php


return [

    // 默認地圖經緯度
    'default_longitude' => env('DEFAULT_LONGITUDE'),
    'default_latitude' => env('DEFAULT_LATITUDE'),

    // 卡最多張數
    'card_max' => env('CARD_MAX', 3),

    // 發票最多張數
    'invoice_max' => env('INVOICE_MAX', 5),

    // 找回密碼發送信箱
    'found_notification_email' => env('FOUND_NOTIFICATION_EMAIL'),

    // 預約過期分鐘數
    'expired_minutes' => env('EXPIRED_MINUTES', 30),

    'phone_code_debug' => env('PHONE_CODE_DEBUG', true),
    'email_code_debug' => env('EMAIL_CODE_DEBUG', true),

    // 註冊成功後給點
    'register_points' => env('REGISTER_POINTS', 100),

    //後台登入到期 180分鐘
    'backend_login_expiration' => env('BACKEND_LOGIN_EXPIRATION', 1440),

    // app登入到期
    'frontend_login_expiration' => 60 * 24 * (int)env('FRONTEND_LOGIN_EXPIRATION', 15),

    // web登入到期
    'web_frontend_login_expiration' => 60 * 24 * (int)env('WEB_FRONTEND_LOGIN_EXPIRATION', 15),

    // 大頭提
    'avatar_list' => [
        ['avatar_type' => 1, 'url' => env('APP_URL').'/avatar/111.png'],
        ['avatar_type' => 2, 'url' => env('APP_URL').'/avatar/222.png'],
        ['avatar_type' => 3, 'url' => env('APP_URL').'/avatar/333.png'],
        ['avatar_type' => 4, 'url' => env('APP_URL').'/avatar/444.png'],
        ['avatar_type' => 5, 'url' => env('APP_URL').'/avatar/555.png'],
    ]

];
