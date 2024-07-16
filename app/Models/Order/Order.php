<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use App\Models\Frontend\User\User;
use App\Models\Parking\ParkingLot;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends BaseModel
{

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'orders';

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

    public function order_refund(): HasOne
    {
        return $this->hasOne(OrderRefund::class, 'order_id', 'id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

}
