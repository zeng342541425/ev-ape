<?php

namespace App\Models\Parking;

use App\Models\BaseModel;

class Favorite extends BaseModel
{

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'favorites';

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    public function __construct(array $attributes = [])
    {

        parent::__construct($attributes);
    }


}
