<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class GuidePagePermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=GuidePagePermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'guidePage.list',
            'title' => 'APP引導頁',
            'icon' => 'el-icon-star-on',
            'path' => '/guidePage',
            'component' => 'guidePage/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'guidePage.create',
            'title' => '創建APP引導頁',
            'icon' => 'el-icon-star-on',
            'path' => 'guidePage/create',
            'component' => 'guidePage/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'guidePage.update',
            'title' => '編輯APP引導頁',
            'icon' => 'el-icon-star-on',
            'path' => 'guidePage/update',
            'component' => 'guidePage/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'guidePage.delete',
            'title' => '刪除APP引導頁',
            'icon' => 'el-icon-star-on',
            'path' => 'guidePage/delete',
            'component' => 'guidePage/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
