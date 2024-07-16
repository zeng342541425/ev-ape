<?php

namespace App\Models\Backend\Admin;


use App\Models\Traits\CommonScope;
use App\Models\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @method static static|Builder notSuperAdmin() 排除超級管理員
 * @method static Builder superAdmin() 超級管理員
 * @method static static|Builder name($name) 名稱查詢
 */
class Role extends \Spatie\Permission\Models\Role
{
    use LogsActivity, SerializeDate, CommonScope;

    protected $table = 'roles';

    protected $attributes = [
        'guard_name' => 'admin'
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
            ->useLogName('role')
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
     * 獲取權限 Ids
     * permission_ids
     * @return Attribute
     */
    public function permissionIds(): Attribute
    {
        return new Attribute(get: function ($value, $attributes) {
            return $this->permissions->pluck('id')->toArray();
        });
    }

    /**
     * 排除超級管理員
     * @method static Builder notSuperAdmin()
     * @param Builder $query
     * @return Builder
     */
    public function scopeNotSuperAdmin(Builder $query)
    {
        return $query->where('name', '<>', 'super_admin');
    }

    /**
     * 超級管理員
     * @method static Builder superAdmin()
     * @param Builder $query
     * @return Builder
     */
    public function scopeSuperAdmin(Builder $query)
    {
        return $query->where('name', 'super_admin');
    }

    /**
     * 同步超級管理員權限
     * @return void
     */
    public static function syncSuperAdminPermissions()
    {
        $role = self::superAdmin()->first();
        if ($role) {
            $allIds = Permission::query()->pluck('id');
            $hasIds = RoleHasPermissions::roleId($role->id)->pluck('permission_id');

            // 新增
            $addIds = $allIds->diff($hasIds);
            if ($addIds->isNotEmpty()) {
                $addList = $addIds->map(function ($id) use ($role) {
                    return [
                        'permission_id' => $id,
                        'role_id' => $role->id
                    ];
                })->all();
                RoleHasPermissions::query()->insert($addList);
            }

            // 刪除
            $rmIds = $hasIds->diff($allIds);
            if ($rmIds->isNotEmpty()) {
                RoleHasPermissions::roleId($role->id)->whereIn('permission_id', $rmIds->all())->delete();
            }

            // 重置已緩存的角色和權限
            (new Permission())->forgetCachedPermissions();

        }
    }

}
