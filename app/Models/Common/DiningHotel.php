<?php

namespace App\Models\Common;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class DiningHotel extends BaseModel
{

    /**
    * 表名
    *
    * @var string
    */
    protected $table = 'dining_hotels';


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


    public function type_list()
    {
        return $this->hasOne(DiningHotelType::class,'id','type_id');
    }

    public function region(): HasOne
    {
        return $this->hasOne(Region::class, 'id', 'region_id');
    }

    public function village(): HasOne
    {
        return $this->hasOne(Region::class, 'id', 'village_id');
    }
}

