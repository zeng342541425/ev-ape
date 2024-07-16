<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class WelcomePagesPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=WelcomePagesPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'welcomePages.list',
            'title' => 'APP歡迎頁',
            'icon' => 'el-icon-star-on',
            'path' => '/welcomePages',
            'component' => 'welcomePages/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'welcomePages.update',
            'title' => '編輯APP歡迎頁',
            'icon' => 'el-icon-star-on',
            'path' => 'welcomePages/update',
            'component' => 'welcomePages/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'welcomePages.delete',
            'title' => '刪除APP歡迎頁',
            'icon' => 'el-icon-star-on',
            'path' => 'welcomePages/delete',
            'component' => 'welcomePages/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
