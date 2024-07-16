<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class FirebaseNoticePermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=FirebaseNoticePermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'firebaseNotice.list',
            'title' => '固定推播',
            'icon' => 'el-icon-star-on',
            'path' => '/firebaseNotice',
            'component' => 'firebaseNotice/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);

        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'firebaseNotice.update',
            'title' => '編輯',
            'icon' => 'el-icon-star-on',
            'path' => 'firebaseNotice/update',
            'component' => 'firebaseNotice/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
