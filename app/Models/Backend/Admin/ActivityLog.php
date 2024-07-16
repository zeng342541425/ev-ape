<?php


namespace App\Models\Backend\Admin;


use App\Models\Traits\CommonScope;
use App\Models\Traits\SerializeDate;
use Spatie\Activitylog\Models\Activity;


class ActivityLog extends Activity
{
    use CommonScope,SerializeDate;

    protected $hidden = [
        'id', 'updated_at',
    ];

}
