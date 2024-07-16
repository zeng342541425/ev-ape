<?php

namespace App\Models\Traits;

use DateTimeInterface;

trait SerializeDate
{
    /**
     * 日期格式化
     *
     * @param DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
