<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * 轉為字符串
 */
class AsString implements CastsAttributes
{

    public function get($model, $key, $value, $attributes)
    {
        return $value;
    }

    public function set($model, $key, $value, $attributes)
    {
        return is_string($value) ? $value : (string)$value;
    }
}
