<?php

namespace App\Models\Backend\System;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @method static static|Builder type($type) 類型
 */
class DictType extends BaseModel
{
    use  LogsActivity;

    protected $table = 'dict_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id'
    ];

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('dict_type')
            ->logFillable()
            ->logUnguarded();
    }

    /**
     * 範圍 - 類型
     * @method static static|Builder type($type) 類型
     * @param $query
     * @param $type
     * @return void
     */
    public function scopeType($query, $type)
    {
        $query->where('type', $type);
    }

    public static function selectAll(): Collection
    {
        return self::query()->select(['id', 'name'])->get();
    }

    /**
     * 字典數據
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dictData()
    {
        return $this->hasMany(DictData::class, 'dict_type_id', 'id');
    }

}
