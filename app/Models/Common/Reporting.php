<?php

namespace App\Models\Common;

use App\Models\BaseModel;
use App\Models\Parking\ChargingPile;
use App\Models\Parking\ParkingLot;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Reporting extends BaseModel
{

    /**
    * 表名
    *
    * @var string
    */
    protected $table = 'reportings';


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

    public function parking(): HasOne
    {
        return $this->hasOne(ParkingLot::class, 'id', 'parking_lot_id');
    }

    public function pile(): HasOne
    {
        return $this->hasOne(ChargingPile::class, 'id', 'pile_id');
    }


}

