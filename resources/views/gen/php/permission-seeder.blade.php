{!! $phpStart !!}

namespace Database\Seeders;

use App\Constants\Constant;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class {{ $className }}PermissionSeeder extends Seeder
{
    /**
    * Run the database seeds.
    * php artisan db:seed --class={{ $className }}PermissionSeeder
    *
    * @return void
    */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $list = Permission::create([
            'pid' => {{ $permissionId }},
            'name' => '{{ $routeName }}.list',
            'title' => '{{ $permissionName }}',
            'icon' => 'el-icon-star-on',
            'path' => '/{{ $routeName }}',
            'component' => '{{ $routeName }}/index',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_NO,
        ]);
        $create = Permission::create([
            'pid' => $list->id,
            'name' => '{{ $routeName }}.create',
            'title' => '新增',
            'icon' => 'el-icon-star-on',
            'path' => '{{ $routeName }}/create',
            'component' => '{{ $routeName }}/create',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $update = Permission::create([
            'pid' => $list->id,
            'name' => '{{ $routeName }}.update',
            'title' => '編輯',
            'icon' => 'el-icon-star-on',
            'path' => '{{ $routeName }}/update',
            'component' => '{{ $routeName }}/update',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);
        $delete = Permission::create([
            'pid' => $list->id,
            'name' => '{{ $routeName }}.delete',
            'title' => '刪除',
            'icon' => 'el-icon-star-on',
            'path' => '{{ $routeName }}/delete',
            'component' => '{{ $routeName }}/delete',
            'guard_name' => 'admin',
            'hidden' => Constant::COMMON_IS_YES,
        ]);

        // 同步超級管理員權限
        Role::syncSuperAdminPermissions();
    }
}
