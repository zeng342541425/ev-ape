<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class AppointmentPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=AppointmentPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'appointment.list',
            'title' => '充電樁預約紀錄',
            'icon' => 'el-icon-star-on',
            'path' => '/appointment',
            'component' => 'appointment/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        // $update = Permission::create([
        //     'pid' => $list->id,
        //     'name' => 'appointment.update',
        //     'title' => '編輯充電樁預約紀錄',
        //     'icon' => 'el-icon-star-on',
        //     'path' => 'appointment/update',
        //     'component' => 'appointment/update',
        //     'guard_name' => 'admin',
        //     'hidden' => Constant::COMMON_IS_YES,
        // ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
