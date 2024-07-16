<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class FaultCategoriesPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=FaultCategoriesPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'faultCategories.list',
            'title' => '表單問題類型',
            'icon' => 'el-icon-star-on',
            'path' => '/faultCategories',
            'component' => 'faultCategories/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'faultCategories.create',
            'title' => '創建表單問題類型',
            'icon' => 'el-icon-star-on',
            'path' => 'faultCategories/create',
            'component' => 'faultCategories/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'faultCategories.update',
            'title' => '編輯表單問題類型',
            'icon' => 'el-icon-star-on',
            'path' => 'faultCategories/update',
            'component' => 'faultCategories/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'faultCategories.delete',
            'title' => '刪除表單問題類型',
            'icon' => 'el-icon-star-on',
            'path' => 'faultCategories/delete',
            'component' => 'faultCategories/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
