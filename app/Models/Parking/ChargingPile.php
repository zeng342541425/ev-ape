<?php

namespace App\Models\Parking;

use App\Models\BaseModel;
use App\Models\Common\Manufacturer;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChargingPile extends BaseModel
{

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'charging_piles';

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    public function __construct(array $attributes = [])
    {

        parent::__construct($attributes);
    }

    public function parking(): HasOne
    {
        return $this->hasOne(ParkingLot::class, 'id', 'parking_lot_id');
    }

    public function manufacturer(): HasOne
    {
        return $this->hasOne(Manufacturer::class, 'id', 'manufacturer_id');
    }

}
