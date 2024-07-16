<?php


namespace App\Models\Backend\Admin;

use App\Casts\NullAsEmptyString;
use App\Models\Traits\CommonScope;
use App\Models\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @method static static|Builder name($name) 名稱查詢
 * @method static static|Builder pid($name) 父級 ID
 */
class Permission extends \Spatie\Permission\Models\Permission
{
    use LogsActivity, SerializeDate, CommonScope;

    protected $table = 'permissions';

    protected $attributes = [
        'guard_name' => 'admin'
    ];

    protected $casts = [
        'active_menu' => NullAsEmptyString::class,
    ];


    /**
     * 添加全局作用域
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope(function ($query) {
            $query->where('guard_name', 'admin');
        });
    }


    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName($this->table)
            ->logUnguarded();
    }

    /**
     * 名稱 範圍
     * @method static static|Builder name($name) 名稱查詢
     * @param $query
     * @param $name
     * @return mixed
     */
    public function scopeName($query, $name)
    {
        return $query->where('name', $name);
    }

    /**
     * pid 範圍
     * @method static static|Builder pid($name) 父級 ID
     * @param $query
     * @param $pid
     * @return mixed
     */
    public function scopePid($query, $pid)
    {
        return $query->where('pid', $pid);
    }
}
