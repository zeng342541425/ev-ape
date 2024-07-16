<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class QuestionCategoryPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=QuestionCategoryPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'questionCategory.list',
            'title' => '常見問題分類',
            'icon' => 'el-icon-star-on',
            'path' => '/questionCategory',
            'component' => 'questionCategory/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'questionCategory.create',
            'title' => '創建常見問題分類',
            'icon' => 'el-icon-star-on',
            'path' => 'questionCategory/create',
            'component' => 'questionCategory/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'questionCategory.update',
            'title' => '編輯常見問題分類',
            'icon' => 'el-icon-star-on',
            'path' => 'questionCategory/update',
            'component' => 'questionCategory/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'questionCategory.delete',
            'title' => '刪除常見問題分類',
            'icon' => 'el-icon-star-on',
            'path' => 'questionCategory/delete',
            'component' => 'questionCategory/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
