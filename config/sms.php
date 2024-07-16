<?php
return [
    'default' => 'mitake',
    // 三竹2
    'mitake_sms' => [
        'username' => env('MITAKE_SMS_USERNAME', 'evapetechofficial'),
        'password' => env('MITAKE_SMS_PASSWORD', 'evape90072145'),
    ],
    //三竹
    'mitake' => [
        'user' => "",
        'pass' => "",
        'domain' => "",
    ],
    //簡訊王
    'kot' => [
        'username' => 'evapetechofficial',
        'password' => 'evape90072145',
    ],

    // 註冊
    'register' => [
        'code_type' => 1,
        'expired_time' => 2 * 60,
        'content' => '猩動力 動態認證，{code}'
    ],

    // 找回密碼
    'reset_passwd' => [
        'code_type' => 2,
        'expired_time' => 2 * 60,
        'content' => '猩動力 動態認證，{code}'
    ],
];
