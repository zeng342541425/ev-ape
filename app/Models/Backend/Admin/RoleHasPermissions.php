<?php

namespace App\Models\Backend\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static static|Builder roleId($role_id = null) 角色 ID
 *
 *
 * @method roleId($role_id = null) 角色 ID
 */
class RoleHasPermissions extends BaseModel
{

    protected $table = 'role_has_permissions';


    /**
     * 範圍 - 角色 ID
     * @method static static|Builder roleId($role_id = null) 角色 ID
     * @param $query
     * @param $role_id
     * @return void
     */
    protected function scopeRoleId($query, $role_id)
    {
        $query->where('role_id', $role_id);
    }
}
