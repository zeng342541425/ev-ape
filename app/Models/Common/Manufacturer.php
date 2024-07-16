<?php

namespace App\Models\Common;

use App\Models\BaseModel;
use Spatie\Activitylog\LogOptions;


class Manufacturer extends BaseModel
{

    /**
    * 表名
    *
    * @var string
    */
    protected $table = 'manufacturers';


    /**
    * 指示模型是否主動維護時間戳.
    *
    * @var bool
    */
    public $timestamps = true;

    /**
    * 不可批量賦值的屬性
    *
    * @var array
    */
    protected $guarded = [
        'id'
    ];

    protected $hidden = ['updated_at'];

}

