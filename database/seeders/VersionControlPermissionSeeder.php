<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class VersionControlPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=VersionControlPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'versionControl.list',
            'title' => '版本控制',
            'icon' => 'el-icon-star-on',
            'path' => '/versionControl',
            'component' => 'versionControl/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'versionControl.update',
            'title' => '編輯',
            'icon' => 'el-icon-star-on',
            'path' => 'versionControl/update',
            'component' => 'versionControl/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
