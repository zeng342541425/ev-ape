<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ChargingPilesPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=ChargingPilesPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'chargingPiles.list',
            'title' => '充電樁管理',
            'icon' => 'el-icon-star-on',
            'path' => '/chargingPiles',
            'component' => 'chargingPiles/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'chargingPiles.create',
            'title' => '新增',
            'icon' => 'el-icon-star-on',
            'path' => 'chargingPiles/create',
            'component' => 'chargingPiles/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'chargingPiles.update',
            'title' => '編輯',
            'icon' => 'el-icon-star-on',
            'path' => 'chargingPiles/update',
            'component' => 'chargingPiles/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'chargingPiles.delete',
            'title' => '刪除',
            'icon' => 'el-icon-star-on',
            'path' => 'chargingPiles/delete',
            'component' => 'chargingPiles/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $firstAudit = Permission::create([
            'pid' => $list->id,
            'name' => 'chargingPiles.audit',
            'title' => '初級審核',
            'icon' => 'el-icon-star-on',
            'path' => 'chargingPiles/audit',
            'component' => 'chargingPiles/audit',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $finalAudit = Permission::create([
            'pid' => $list->id,
            'name' => 'chargingPiles.final',
            'title' => '終級審核',
            'icon' => 'el-icon-star-on',
            'path' => 'chargingPiles/final',
            'component' => 'chargingPiles/final',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
