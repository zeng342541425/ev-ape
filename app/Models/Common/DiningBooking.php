<?php

namespace App\Models\Common;

use App\Models\BaseModel;
use App\Models\Frontend\User\User;
use Illuminate\Database\Eloquent\Relations\HasOne;


class DiningBooking extends BaseModel
{

    /**
    * 表名
    *
    * @var string
    */
    protected $table = 'dining_bookings';


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

    public function dining_hotel(): HasOne
    {
        return $this->hasOne(DiningHotel::class, 'id', 'dining_hotel_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}

