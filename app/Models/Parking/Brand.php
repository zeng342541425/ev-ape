<?php

namespace App\Models\Parking;

use App\Models\BaseModel;

class Brand extends BaseModel
{

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'brands';

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    public function __construct(array $attributes = [])
    {

        parent::__construct($attributes);
    }

}
