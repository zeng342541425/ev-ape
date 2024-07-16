<?php

namespace App\Models\Backend\System;

use App\Constants\Constant;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @method static static|Builder name($name) 名稱查詢
 */
class GenTable extends BaseModel
{
    use  LogsActivity;

    protected $table = 'gen_tables';


    protected $guarded = [
        'id'
    ];

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('genTable')
            ->logFillable()
            ->logUnguarded();
    }

    /**
     * 表字段
     * @return HasMany
     */
    public function genTableColumns(): HasMany
    {
        return $this->hasMany(GenTableColumn::class);
    }

    /**
     * 名稱查詢
     * @method static static|Builder name($name) 名稱查詢
     * @param $query
     * @param $name
     * @return void
     */
    public function scopeName($query, $name)
    {
        $query->where('name', $name);
    }

}
