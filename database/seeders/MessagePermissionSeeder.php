<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class MessagePermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=MessagePermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'message.list',
            'title' => '最新消息',
            'icon' => 'el-icon-star-on',
            'path' => '/message',
            'component' => 'message/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'message.create',
            'title' => '新增',
            'icon' => 'el-icon-star-on',
            'path' => 'message/create',
            'component' => 'message/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'message.update',
            'title' => '編輯',
            'icon' => 'el-icon-star-on',
            'path' => 'message/update',
            'component' => 'message/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'message.delete',
            'title' => '刪除',
            'icon' => 'el-icon-star-on',
            'path' => 'message/delete',
            'component' => 'message/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
