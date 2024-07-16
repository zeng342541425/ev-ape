<?php

namespace App\Models\Common;

use App\Models\BaseModel;
use App\Models\Parking\ParkingLot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class Questions extends BaseModel
{

    /**
    * 表名
    *
    * @var string
    */
    protected $table = 'questions';


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

    public function category(): HasOne
    {
        return $this->hasOne(QuestionCategory::class, 'id', 'category_id');
    }
}

