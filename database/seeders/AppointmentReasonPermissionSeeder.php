<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class AppointmentReasonPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=AppointmentReasonPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'appointmentReason.list',
            'title' => '取消充電預約原因管理',
            'icon' => 'el-icon-star-on',
            'path' => '/appointmentReason',
            'component' => 'appointmentReason/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'appointmentReason.create',
            'title' => '創建取消充電預約原因管理',
            'icon' => 'el-icon-star-on',
            'path' => 'appointmentReason/create',
            'component' => 'appointmentReason/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'appointmentReason.update',
            'title' => '編輯取消充電預約原因管理',
            'icon' => 'el-icon-star-on',
            'path' => 'appointmentReason/update',
            'component' => 'appointmentReason/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'appointmentReason.delete',
            'title' => '刪除取消充電預約原因管理',
            'icon' => 'el-icon-star-on',
            'path' => 'appointmentReason/delete',
            'component' => 'appointmentReason/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
