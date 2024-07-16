<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class OrderRefundPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=OrderRefundPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'orderRefund.list',
            'title' => '退款紀錄',
            'icon' => 'el-icon-star-on',
            'path' => '/orderRefund',
            'component' => 'orderRefund/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
