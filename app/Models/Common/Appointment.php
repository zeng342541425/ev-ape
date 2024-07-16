<?php

namespace App\Models\Common;

use App\Models\Backend\User\User;
use App\Models\BaseModel;
use App\Models\Parking\ChargingPile;
use App\Models\Parking\ParkingLot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class Appointment extends BaseModel
{

    /**
    * 表名
    *
    * @var string
    */
    protected $table = 'appointments';


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

    public function userinfo(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function cancellation(): HasOne
    {
        return $this->hasOne(AppointmentCancellation::class, 'appointment_id', 'id');
    }

}

