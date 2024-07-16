<?php

namespace App\Models\Common;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;


class DiningHotelAuditLog extends BaseModel
{

    /**
    * 表名
    *
    * @var string
    */
    protected $table = 'dining_hotel_audit_logs';


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

    public function seat_info(): HasMany
    {
        return $this->hasMany(DiningSeat::class, 'dining_hotel_id', 'id');
    }

}

