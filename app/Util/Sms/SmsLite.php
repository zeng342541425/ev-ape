<?php

namespace App\Util\Sms;

use Illuminate\Support\Str;

class SmsLite
{
    public static function driver(string $driver): Kot|Mitake
    {
        if (!$driver) {
            $driver = config('sms.default');
        }
        $driver = Str::studly($driver);
        return new $driver;
    }

}
