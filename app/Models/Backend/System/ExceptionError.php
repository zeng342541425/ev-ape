<?php

namespace App\Models\Backend\System;

use App\Models\BaseModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;

class ExceptionError extends BaseModel
{

    protected $table = 'exception_errors';

    /**
     * 可以被批量賦值的屬性。
     *
     * @var array
     */
    protected $guarded = [
        'id'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'line' => 'integer',
        'trace' => 'array'
    ];

    /**
     * 自定義異常字符串
     *
     * @param $value
     */
    public function setTraceAsStringAttribute($value): void
    {
        $this->attributes['trace_as_string'] =
            '[' . Carbon::now()->format('Y-m-d H:i:s') . '] ' . App::environment() . '.ERROR: '
            . $this->attributes['message']
            . ' at ' . $this->attributes['file'] . ':' . $this->attributes['line']
            . "\n"
            . $value;
    }
}
