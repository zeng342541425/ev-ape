<?php

namespace App\Models\User;

use App\Models\BaseModel;

class CreditCard extends BaseModel
{

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'user_credit_cards';

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    public function __construct(array $attributes = [])
    {

        parent::__construct($attributes);
    }

}
