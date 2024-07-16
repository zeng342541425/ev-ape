<?php

namespace App\Models\User;

use App\Models\BaseModel;

class CardRequests extends BaseModel
{

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'card_requests';

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    public function __construct(array $attributes = [])
    {

        parent::__construct($attributes);
    }

}
