<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use App\Models\Parking\ParkingLot;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderRefund extends BaseModel
{

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'order_refunds';

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    public function __construct(array $attributes = [])
    {

        parent::__construct($attributes);
    }

}
