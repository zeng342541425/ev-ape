<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderNotifyRequest extends BaseModel
{

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'order_notify_requests';

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    public function __construct(array $attributes = [])
    {

        parent::__construct($attributes);
    }

}
