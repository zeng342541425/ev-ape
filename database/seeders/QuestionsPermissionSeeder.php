<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class QuestionsPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=QuestionsPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'questions.list',
            'title' => '問題管理',
            'icon' => 'el-icon-star-on',
            'path' => '/questions',
            'component' => 'questions/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'questions.create',
            'title' => '創建問題管理',
            'icon' => 'el-icon-star-on',
            'path' => 'questions/create',
            'component' => 'questions/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'questions.update',
            'title' => '編輯問題管理',
            'icon' => 'el-icon-star-on',
            'path' => 'questions/update',
            'component' => 'questions/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'questions.delete',
            'title' => '刪除問題管理',
            'icon' => 'el-icon-star-on',
            'path' => 'questions/delete',
            'component' => 'questions/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
