<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class BannerPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=BannerPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'banner.list',
            'title' => '首頁輪播圖',
            'icon' => 'el-icon-star-on',
            'path' => '/banner',
            'component' => 'banner/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'banner.create',
            'title' => '創建首頁輪播圖',
            'icon' => 'el-icon-star-on',
            'path' => 'banner/create',
            'component' => 'banner/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'banner.update',
            'title' => '編輯首頁輪播圖',
            'icon' => 'el-icon-star-on',
            'path' => 'banner/update',
            'component' => 'banner/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'banner.delete',
            'title' => '刪除首頁輪播圖',
            'icon' => 'el-icon-star-on',
            'path' => 'banner/delete',
            'component' => 'banner/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
