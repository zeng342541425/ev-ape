<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DiningHotelPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=DiningHotelPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'diningHotel.list',
            'title' => '餐旅設定',
            'icon' => 'el-icon-star-on',
            'path' => '/diningHotel',
            'component' => 'diningHotel/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'diningHotel.create',
            'title' => '新增',
            'icon' => 'el-icon-star-on',
            'path' => 'diningHotel/create',
            'component' => 'diningHotel/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'diningHotel.update',
            'title' => '編輯',
            'icon' => 'el-icon-star-on',
            'path' => 'diningHotel/update',
            'component' => 'diningHotel/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'diningHotel.delete',
            'title' => '刪除',
            'icon' => 'el-icon-star-on',
            'path' => 'diningHotel/delete',
            'component' => 'diningHotel/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $firstAudit = Permission::create([
            'pid' => $list->id,
            'name' => 'diningHotel.audit',
            'title' => '初級審核',
            'icon' => 'el-icon-star-on',
            'path' => 'diningHotel/audit',
            'component' => 'diningHotel/audit',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $finalAudit = Permission::create([
            'pid' => $list->id,
            'name' => 'diningHotel.final',
            'title' => '終級審核',
            'icon' => 'el-icon-star-on',
            'path' => 'diningHotel/final',
            'component' => 'diningHotel/final',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
