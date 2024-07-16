<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ReportingPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=ReportingPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'reporting.list',
            'title' => '充電樁報修列表',
            'icon' => 'el-icon-star-on',
            'path' => '/reporting',
            'component' => 'reporting/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'reporting.create',
            'title' => '創建充電樁報修列表',
            'icon' => 'el-icon-star-on',
            'path' => 'reporting/create',
            'component' => 'reporting/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'reporting.update',
            'title' => '編輯充電樁報修列表',
            'icon' => 'el-icon-star-on',
            'path' => 'reporting/update',
            'component' => 'reporting/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
