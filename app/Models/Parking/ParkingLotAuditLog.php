<?php

namespace App\Models\Parking;

use App\Models\BaseModel;

class ParkingLotAuditLog extends BaseModel
{

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'parking_lot_audit_logs';

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    public function __construct(array $attributes = [])
    {

        parent::__construct($attributes);
    }

}
