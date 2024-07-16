<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ChargingPowersPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=ChargingPowersPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'chargingPowers.list',
            'title' => '功率規格管理',
            'icon' => 'el-icon-star-on',
            'path' => '/chargingPowers',
            'component' => 'chargingPowers/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'chargingPowers.create',
            'title' => '新增',
            'icon' => 'el-icon-star-on',
            'path' => 'chargingPowers/create',
            'component' => 'chargingPowers/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'chargingPowers.update',
            'title' => '編輯',
            'icon' => 'el-icon-star-on',
            'path' => 'chargingPowers/update',
            'component' => 'chargingPowers/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'chargingPowers.delete',
            'title' => '刪除',
            'icon' => 'el-icon-star-on',
            'path' => 'chargingPowers/delete',
            'component' => 'chargingPowers/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
