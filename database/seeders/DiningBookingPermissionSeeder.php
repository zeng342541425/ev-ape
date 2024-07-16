<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DiningBookingPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=DiningBookingPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'diningBooking.list',
            'title' => '餐旅預約列表',
            'icon' => 'el-icon-star-on',
            'path' => '/diningBooking',
            'component' => 'diningBooking/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'diningBooking.update',
            'title' => '編輯',
            'icon' => 'el-icon-star-on',
            'path' => 'diningBooking/update',
            'component' => 'diningBooking/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
