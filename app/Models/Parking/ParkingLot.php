<?php

namespace App\Models\Parking;

use App\Models\BaseModel;
use App\Models\Common\Region;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ParkingLot extends BaseModel
{

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'parking_lots';

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    public function __construct(array $attributes = [])
    {

        parent::__construct($attributes);
    }

    public function region(): HasOne
    {
        return $this->hasOne(Region::class, 'id', 'region_id');
    }

    public function village(): HasOne
    {
        return $this->hasOne(Region::class, 'id', 'village_id');
    }

    public function favorite_with(): HasOne
    {
        return $this->hasOne(Favorite::class, 'parking_lot_id', 'id');
    }

}
