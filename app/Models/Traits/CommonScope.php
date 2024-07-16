<?php

namespace App\Models\Traits;

use App\Constants\Constant;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static static|Builder status($status = null) 狀態
 * @method static static|Builder timeBetween(string $field = 'created_at', array $datetime = []) 時間範圍
 * @method static static|Builder timeBetweenTime(string $start_field, string $end_field, array $datetime = []) 時間與時間範圍
 * @method static static|Builder like(string $field, $value, string $side = 'both', $isNotLike = false, $isAnd = true) 模糊查詢
 * @method static static|Builder notLike($field, $value, $side = 'both', $isAnd = true) 模糊查詢 非
 * @method static static|Builder query() Query
 *
 * @method status($status = null) 狀態
 * @method timeBetween(string $field = 'created_at', array $datetime = []) 時間範圍
 * @method timeBetweenTime(string $start_field, string $end_field, array $datetime = []) 時間與時間範圍
 * @method like(string $field, $value, string $side = 'both', $isNotLike = false, $isAnd = true) 模糊查詢
 * @method notLike($field, $value, $side = 'both', $isAnd = true) 模糊查詢 非
 */
trait CommonScope
{

    /**
     * 狀態 默認啟用
     * @param $query
     * @param $status
     * @return void
     */
    protected function scopeStatus($query, $status = Constant::COMMON_STATUS_ENABLE)
    {
        $query->where('status', $status);
    }

    /**
     * 時間範圍
     * @method timeBetween(string $field = 'created_at', array $datetime = [])
     * @param $query
     * @param string $field
     * @param array $datetime
     * @return void
     */
    protected function scopeTimeBetween($query, string $field = 'created_at', array $datetime = [])
    {
        $start_time = $datetime[0] ?? null;
        $end_time = $datetime[1] ?? null;
        if (!empty($start_time) && !empty($end_time)) {
            $query->whereBetween($field, [$start_time, $end_time]);
        } elseif (!empty($start_time) && empty($end_time)) {
            $query->where($field, '>=', $start_time);
        } elseif (empty($start_time) && !empty($end_time)) {
            $query->where($field, '<=', $end_time);
        }
    }

    /**
     * 時間與時間範圍
     * @method timeBetweenTime(string $start_field, string $end_field, array $datetime = [])
     * @param $query
     * @param string $start_field
     * @param string $end_field
     * @param array $datetime
     * @return void
     */
    protected function scopeTimeBetweenTime($query, string $start_field, string $end_field, array $datetime = [])
    {
        $start_time = $datetime[0] ?? null;
        $end_time = $datetime[1] ?? null;
        if (!empty($start_time) && !empty($end_time)) {
            $query->where([
                [$start_field, '<=', $end_time],
                [$end_field, '>=', $start_time]
            ]);
        } elseif (!empty($start_time)) {
            $query->where($start_field, '>=', $start_time);
        } elseif (!empty($end_time)) {
            $query->where($end_field, '<=', $end_time);
        }
    }

    /**
     * 模糊查詢
     * @method like(string $field, $value, string $side = 'both', $isNotLike = false, $isAnd = true)
     * @param $query
     * @param $field
     * @param $value
     * @param string $side
     * @param $isNotLike
     * @param $isAnd
     * @return mixed
     */
    public function scopeLike($query, $field, $value, string $side = 'both', $isNotLike = false, $isAnd = true)
    {
        $operator = $isNotLike ? 'not like' : 'like';

        $escape_like_str = function ($str) {

            $like_escape_char = '\\';

            $str = addslashes($str);

            return str_replace(['!', '%', '_'], [
                $like_escape_char . '!',
                $like_escape_char . '%',
                $like_escape_char . '_',
            ], $str);
        };

        if ($side == 'none') {
            $value = $escape_like_str($value);
        } elseif ($side == 'before' || $side == 'left') {
            $value = "%{$escape_like_str($value)}";
        } elseif ($side == 'after' || $side == 'right') {
            $value = "{$escape_like_str($value)}%";
        } else {
            $value = "%{$escape_like_str($value)}%";
        }

        return $isAnd ? $query->where($field, $operator, $value) : $query->orWhere($field, $operator, $value);
    }

    /**
     * 模糊查詢 非
     * @method notLike($field, $value, $side = 'both', $isAnd = true)
     * @param $query
     * @param $field
     * @param $value
     * @param $side
     * @param $isAnd
     * @return mixed
     */
    public function scopeNotLike($query, $field, $value, $side = 'both', $isAnd = true)
    {
        return $query->like($field, $value, $side, true, $isAnd);
    }

}
