<?php
/**
 * Title:
 * User: liuj
 * Date: 2023/2/13/013
 * Time: 16:43
 */

namespace Database\Seeders;

use App\Models\Backend\Admin\Admin;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $sql = file_get_contents(base_path('database/seeders/feibi-base.sql'));
        DB::unprepared($sql);

        // 重新設置密碼
        Admin::superAdmin()->update([
            'password' => Hash::make('123456')
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();

    }

}
