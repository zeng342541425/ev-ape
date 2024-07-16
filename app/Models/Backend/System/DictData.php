<?php

namespace App\Models\Backend\System;

use App\Constants\Constant;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @method static static|Builder dictTypeId($id) 類型 ID
 */
class DictData extends BaseModel
{
    use  LogsActivity;

    protected $table = 'dict_data';


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
            ->useLogName('dict_data')
            ->logFillable()
            ->logUnguarded();
    }

    /**
     * 類型 ID
     * @method static static|Builder dictTypeId($id) 類型 ID
     * @param $query
     * @param $id
     * @return void
     */
    public function scopeDictTypeId($query, $id)
    {
        $query->where('dict_type_id', $id);
    }


    /**
     * 設置默認
     * @param DictData $data
     * @return void
     */
    public static function setDefault(DictData $data): void
    {
        self::dictTypeId($data->dict_type_id)
            ->where('id', '!=', $data->id)
            ->update([
                'default' => Constant::COMMON_IS_NO,
                'updated_at' => now()
            ]);
    }

    public static function selectAll(): Collection
    {
        try {
            $data = Cache::store('redis')->get('DictData', collect([]));
        } catch (InvalidArgumentException) {
            $data = collect([]);
        }
        if ($data->count() === 0) {
            $data = DictData::leftJoin(
                'dict_types',
                'dict_types.id',
                '=',
                'dict_data.dict_type_id'
            )->where(
                'dict_types.status', '=', 1
            )->where(
                'dict_data.status', '=', 1
            )->select([
                'dict_data.dict_type_id',
                'dict_data.sort',
                'dict_data.label',
                'dict_data.value',
                'dict_data.list_class',
                'dict_data.default'
            ])->get();
            Cache::store('redis')->put('DictData', $data);
        }

        return $data;
    }

    public static function forgetRedis(): void
    {
        Cache::store('redis')->forget('DictData');
    }
}
