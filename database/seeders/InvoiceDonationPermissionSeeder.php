<?php

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class InvoiceDonationPermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class=InvoiceDonationPermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => 0,
            'name' => 'invoiceDonation.list',
            'title' => '發票捐贈設定',
            'icon' => 'el-icon-star-on',
            'path' => '/invoiceDonation',
            'component' => 'invoiceDonation/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => 'invoiceDonation.create',
            'title' => '創建發票捐贈設定',
            'icon' => 'el-icon-star-on',
            'path' => 'invoiceDonation/create',
            'component' => 'invoiceDonation/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => 'invoiceDonation.update',
            'title' => '編輯發票捐贈設定',
            'icon' => 'el-icon-star-on',
            'path' => 'invoiceDonation/update',
            'component' => 'invoiceDonation/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => 'invoiceDonation.delete',
            'title' => '刪除發票捐贈設定',
            'icon' => 'el-icon-star-on',
            'path' => 'invoiceDonation/delete',
            'component' => 'invoiceDonation/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
