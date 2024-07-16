<?php

namespace App\Models\Parking;

use App\Models\BaseModel;

class Booking extends BaseModel
{

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'bookings';

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    public function __construct(array $attributes = [])
    {

        parent::__construct($attributes);
    }

}
