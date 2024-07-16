<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class BrandsPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=BrandsPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'brands.list',
            'title' => '車用品牌',
            'icon' => 'el-icon-star-on',
            'path' => '/brands',
            'component' => 'brands/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'brands.create',
            'title' => '創建車用品牌',
            'icon' => 'el-icon-star-on',
            'path' => 'brands/create',
            'component' => 'brands/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'brands.update',
            'title' => '編輯車用品牌',
            'icon' => 'el-icon-star-on',
            'path' => 'brands/update',
            'component' => 'brands/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'brands.delete',
            'title' => '刪除車用品牌',
            'icon' => 'el-icon-star-on',
            'path' => 'brands/delete',
            'component' => 'brands/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
