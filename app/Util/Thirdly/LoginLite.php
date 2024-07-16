<?php

namespace App\Util\Thirdly;

use Illuminate\Support\Str;

class LoginLite
{
    /**
     * @param int $driver
     * @return Apple|Facebook|Google|Line
     * @throws \Exception
     */
    public static function driver(int $driver): Apple|Facebook|Google|Line
    {

        return match ($driver) {
            1 => new Google(),
            2 => new Facebook(),
            3 => new Line(),
            4 => new Apple(),
            default => throw new \Exception("暫不支持$driver"),
        };
    }


}
