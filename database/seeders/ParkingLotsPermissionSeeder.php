<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ParkingLotsPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=ParkingLotsPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'parkingLots.list',
            'title' => '充電場域管理',
            'icon' => 'el-icon-star-on',
            'path' => '/parkingLots',
            'component' => 'parkingLots/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'parkingLots.create',
            'title' => '創建',
            'icon' => 'el-icon-star-on',
            'path' => 'parkingLots/create',
            'component' => 'parkingLots/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'parkingLots.update',
            'title' => '編輯',
            'icon' => 'el-icon-star-on',
            'path' => 'parkingLots/update',
            'component' => 'parkingLots/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'parkingLots.delete',
            'title' => '刪除',
            'icon' => 'el-icon-star-on',
            'path' => 'parkingLots/delete',
            'component' => 'parkingLots/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $firstAudit = Permission::create([
            'pid' => $list->id,
            'name' => 'parkingLots.audit',
            'title' => '初級審核',
            'icon' => 'el-icon-star-on',
            'path' => 'parkingLots/audit',
            'component' => 'parkingLots/audit',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $finalAudit = Permission::create([
            'pid' => $list->id,
            'name' => 'parkingLots.final',
            'title' => '終級審核',
            'icon' => 'el-icon-star-on',
            'path' => 'parkingLots/final',
            'component' => 'parkingLots/final',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
