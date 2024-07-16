<?php

namespace App\Models\User;

use App\Models\BaseModel;

class Invoice extends BaseModel
{

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'invoices';

    protected $guarded = [];

    // protected $hidden = ['updated_at'];

    public function __construct(array $attributes = [])
    {

        parent::__construct($attributes);
    }

}
