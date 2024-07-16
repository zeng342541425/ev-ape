<?php

namespace App\Models\Common;

use App\Models\BaseModel;

class PileError extends BaseModel
{

    /**
    * 表名
    *
    * @var string
    */
    protected $table = 'pile_errors';


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

    protected $hidden = [
        'updated_at'
    ];

}

