<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ContactUsPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=ContactUsPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'contactUs.list',
            'title' => '聯絡我們管理',
            'icon' => 'el-icon-star-on',
            'path' => '/contactUs',
            'component' => 'contactUs/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);

        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'contactUs.update',
            'title' => '編輯聯絡我們管理',
            'icon' => 'el-icon-star-on',
            'path' => 'contactUs/update',
            'component' => 'contactUs/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);


        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
