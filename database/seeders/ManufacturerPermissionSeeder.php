<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ManufacturerPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=ManufacturerPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'manufacturer.list',
            'title' => '充電樁廠家',
            'icon' => 'el-icon-star-on',
            'path' => '/manufacturer',
            'component' => 'manufacturer/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'manufacturer.create',
            'title' => '新增',
            'icon' => 'el-icon-star-on',
            'path' => 'manufacturer/create',
            'component' => 'manufacturer/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'manufacturer.update',
            'title' => '編輯',
            'icon' => 'el-icon-star-on',
            'path' => 'manufacturer/update',
            'component' => 'manufacturer/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'manufacturer.delete',
            'title' => '刪除',
            'icon' => 'el-icon-star-on',
            'path' => 'manufacturer/delete',
            'component' => 'manufacturer/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
