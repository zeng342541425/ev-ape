<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class FirebasePushPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=FirebasePushPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'firebasePush.list',
            'title' => '公告推播',
            'icon' => 'el-icon-star-on',
            'path' => '/firebasePush',
            'component' => 'firebasePush/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'firebasePush.create',
            'title' => '新增',
            'icon' => 'el-icon-star-on',
            'path' => 'firebasePush/create',
            'component' => 'firebasePush/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'firebasePush.update',
            'title' => '編輯',
            'icon' => 'el-icon-star-on',
            'path' => 'firebasePush/update',
            'component' => 'firebasePush/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'firebasePush.delete',
            'title' => '刪除',
            'icon' => 'el-icon-star-on',
            'path' => 'firebasePush/delete',
            'component' => 'firebasePush/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
