<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class AdvertisementPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=AdvertisementPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'advertisement.list',
            'title' => '廣告蓋版設定',
            'icon' => 'el-icon-star-on',
            'path' => '/advertisement',
            'component' => 'advertisement/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'advertisement.create',
            'title' => '新增',
            'icon' => 'el-icon-star-on',
            'path' => 'advertisement/create',
            'component' => 'advertisement/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'advertisement.update',
            'title' => '編輯',
            'icon' => 'el-icon-star-on',
            'path' => 'advertisement/update',
            'component' => 'advertisement/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'advertisement.delete',
            'title' => '刪除',
            'icon' => 'el-icon-star-on',
            'path' => 'advertisement/delete',
            'component' => 'advertisement/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
