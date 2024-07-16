<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class FaultsPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=FaultsPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'faults.list',
            'title' => '客服表單',
            'icon' => 'el-icon-star-on',
            'path' => '/faults',
            'component' => 'faults/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        // $create = Permission::create([
        //     'pid' => $list->id,
        //     'name' => 'faults.create',
        //     'title' => '創建客服表單',
        //     'icon' => 'el-icon-star-on',
        //     'path' => 'faults/create',
        //     'component' => 'faults/create',
        //     'guard_name' => 'admin',
        //     'hidden' => Constant::COMMON_IS_YES,
        // ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'faults.update',
            'title' => '編輯客服表單',
            'icon' => 'el-icon-star-on',
            'path' => 'faults/update',
            'component' => 'faults/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        // $delete = Permission::create([
        //     'pid' => $list->id,
        //     'name' => 'faults.delete',
        //     'title' => '刪除客服表單',
        //     'icon' => 'el-icon-star-on',
        //     'path' => 'faults/delete',
        //     'component' => 'faults/delete',
        //     'guard_name' => 'admin',
        //     'hidden' => Constant::COMMON_IS_YES,
        // ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
