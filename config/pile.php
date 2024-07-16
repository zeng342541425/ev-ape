<?php


return [

    // token
    'token' => env('PILE_TOKEN'),

    // 過期秒數
    'expired_time' => 24*60*60*15,

    // domain
    'domain' => env('PILE_DOMAIN', 'https://ems-dev.idl.com.tw'),
    'codename' => env('CODENAME', 'casaloma'),
    'charging_debug' => env('CHARGING_DEBUG', false),


];
