<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * 轉為字符串
 */
class NullAsEmptyString implements CastsAttributes
{

    public function get($model, $key, $value, $attributes)
    {
        return $value;
    }

    public function set($model, $key, $value, $attributes)
    {
        return $value ?? '';
    }
}
