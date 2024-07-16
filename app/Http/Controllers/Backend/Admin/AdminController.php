<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Admin\Admin\CreateRequest;
use App\Http\Requests\Backend\Admin\Admin\ListRequest;
use App\Http\Requests\Backend\Admin\Admin\SyncPermissionsRequest;
use App\Http\Requests\Backend\Admin\Admin\SyncRolesRequest;
use App\Http\Requests\Backend\Admin\Admin\UpdateRequest;
use App\Http\Requests\Backend\Admin\Admin\UpdateSelfRequest;
use App\Http\Requests\Common\IdRequest;
use App\Models\Backend\Admin\Admin;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use App\Notifications\PermissionChange;
use App\Notifications\RoleChange;
use App\Util\Routes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    /**
     * 管理員列表
     * @param ListRequest $request
     * @return Response
     */
    public function list(ListRequest $request): Response
    {
        $param = $request->all();

        $my_self = Auth::user();

        $query = Admin::query()->with([
            'roles'
        ]);

        $query->where('username', '<>', Admin::$super_admin);

        if (isset($param['name']) && $param['name'] != '') {
            $query->like('name', $param['name']);
        }
        if (isset($param['username']) && $param['username'] != '') {
            $query->like('username', $param['username']);
        }
        if (!empty($param['status'])) {
            $query->where('status', $param['status']);
        }
        if (!empty($param['role_ids'])) {
            $query->whereHasIn('modelHasRoles', function ($query) use ($param) {
                $query->whereIn('role_id', $param['role_ids']);
            });
        }
        if (!empty($param['created_at'])) {
            $query->timeBetween('created_at', $param['created_at']);
        }
        if (!empty($param['sort']) && !empty($param['order'])) {
            $query->orderBy($param['sort'], order_direction($param['order']));
        } else {
            $query->orderByDesc('created_at');
        }

        $list = $query->paginate($param['limit']);

        $list->each(function ($item) {
            $item->append(['role_ids']);
            $item->roles->each(function ($item) {
                $item->makeHidden([
                    'pivot'
                ]);
            });
        });


        return $this->success([
            'list' => $list->items(),
            'total' => $list->total(),
        ]);
    }

    /**
     * 管理員詳情
     * @param IdRequest $request
     * @return Response
     */
    public function detail(IdRequest $request): Response
    {
        $id = $request->post('id');
        $admin = Admin::query()->with([
            'roles'
        ])->find($id);

        if (!$admin) {
            return $this->error(__('message.data_not_found'));
        }
        $admin->append('role_ids');

        $permissions = $admin->getDirectPermissions();
        $admin['permission_ids'] = $permissions->pluck('id');

        $admin->append([
            'role_ids',
        ])->makeHidden([
            'permissions', 'roles'
        ]);

        return $this->success([
            'item' => $admin
        ]);

    }

    /**
     * 創建管理員
     *
     * @param CreateRequest $request
     * @return Response
     */
    public function create(CreateRequest $request): Response
    {
        $param = $request->only([
            'username',
            'name',
            // 'email',
            'password',
            'status'
        ]);

        // 檢測 用戶名和信箱 是否已存在
        $exist = Admin::query()->where('username', $param['username'])->first();
        if ($exist) {
            if ($exist->username == $param['username']) {
                return $this->error(__('message.admin.username_exist'));
            }
            return $this->error(__('message.admin.email_exist'));
        }

        $param['password'] = Hash::make($param['password']);

        $admin = Admin::query()->create($param);

        return $this->success([
            'item' => $admin
        ]);
    }

    /**
     * 所有管理員
     * @return \Illuminate\Http\JsonResponse
     */
    public function all(Request $request)
    {

        $list = Admin::query()->select([
            'id', 'username', 'name', 'status'
        ])->get();

        return $this->success([
            'list' => $list
        ], __('message.common.search.success'));
    }

    /**
     * 更新管理員
     *
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(UpdateRequest $request): Response
    {
        $id = $request->post('id');
        $param = $request->only([
            'username',
            'name',
            // 'email',
            'password',
            'status'
        ]);

        $admin = Admin::notSuperAdmin()->find($id);
        if (!$admin) {
            return $this->error('message.data_not_found');
        }

        // 檢測 用戶名和信箱 是否已存在
        $exist = Admin::query()->where('username', $param['username'])->where('id', '<>', $id)->first();
        if ($exist) {
            if ($exist->username == $param['username']) {
                return $this->error(__('message.admin.username_exist'));
            }
            return $this->error(__('message.admin.email_exist'));
        }

        if (!empty($param['password'])) {
            $param['password'] = Hash::make($param['password']);
        } else {
            unset($param['password']);
        }

        if ($admin->update($param)) {
            return $this->success([
                'item' => $admin
            ], __('message.common.update.success'));

        }

        return $this->error(__('message.common.update.fail'));
    }

    /**
     * 刪除管理員
     * @param IdRequest $request
     * @return Response
     */
    public function delete(IdRequest $request): Response
    {
        $id = $request->post('id');
        $admin = Admin::notSuperAdmin()->find($id);
        if (!$admin) {
            return $this->error('message.data_not_found');
        }

        $admin->givePermissionTo([]); // 移除權限
        $admin->syncRoles([]); // 移除角色
        $admin->delete();

        return $this->success(null, __('message.common.delete.success'));
    }

    /**
     * 自身更新
     *
     * @param UpdateSelfRequest $request
     * @return Response
     */
    public function updateSelf(UpdateSelfRequest $request): Response
    {
        $param = $request->only(['name', 'password', 'old_password']);
        $admin = $request->user();

        // 檢測 信箱 是否已存在
        // if ($param['email'] != $admin->email) {
        //     $exist = Admin::email($param['email'])->where('id', '<>', $admin->id)->first();
        //     if ($exist) {
        //         return $this->error(__('message.admin.email_exist'));
        //     }
        // }

        if (!empty($param['password']) && !empty($param['old_password'])) {
            $param['password'] = Hash::make($param['password']);

            if (!Hash::check($param['old_password'], $admin->password)) {
                return $this->error(__('message.common.update.fail'));
            }

            unset($param['old_password']);
        } else {
            unset($param['password']);
        }

        if ($admin->update($param)) {
            return $this->success([
                'item' => $admin
            ], __('message.common.update.success'));
        }
        return $this->error(__('message.common.update.fail'));
    }

    /**
     * 設置身份
     * @param SyncRolesRequest $request
     * @return Response
     */
    public function syncRoles(SyncRolesRequest $request): Response
    {
        $id = $request->post('id');
        $role_ids = $request->post('role_ids', []);

        $admin = Admin::notSuperAdmin()->find($id);
        if (!$admin) {
            return $this->error(__('message.data_not_found'));
        }

        if (!empty($role_ids)) {
            $role_ids = Role::notSuperAdmin()->whereIn('id', $role_ids)->pluck('id')->toArray();
        } else {
            $role_ids = [];
        }

        // 設置角色
        $admin->syncRoles($role_ids);

        // 操作日誌
        activity()
            ->useLog('admin')
            ->performedOn(new Admin())
            ->causedBy($request->user())
            ->withProperties([
                'id' => $id,
                'role_ids' => $role_ids
            ])->log('update roles');

        // 通知
        $admin->notify(new RoleChange($role_ids));

        return $this->success(msg: __('message.common.update.success'));
    }

    /**
     * 授權權限
     *
     * @param SyncPermissionsRequest $request
     * @return Response
     */
    public function syncPermissions(SyncPermissionsRequest $request): Response
    {
        $id = $request->post('id');
        $permission_ids = $request->post('permission_ids', []);

        $admin = Admin::notSuperAdmin()->find($id);
        if (!$admin) {
            return $this->error(__('message.data_not_found'));
        }

        // 查詢存在的權限
        if (!empty($permission_ids)) {
            $permission_ids = array_unique($permission_ids);
            $permission_ids = Permission::query()->whereIn('id', $permission_ids)->pluck('id')->toArray();
        }

        // 設置權限
        $admin->syncPermissions($permission_ids);

        // 操作記錄
        activity()
            ->useLog('admin')
            ->performedOn($admin)
            ->causedBy($request->user())
            ->withProperties([
                'id' => $id,
                'permission_ids' => $permission_ids
            ])->log('update permissions');

        // 通知
        $admin->notify(new PermissionChange($permission_ids));

        return $this->success(__('message.common.update.success'));
    }

    /**
     * @param Request $request
     * @return Response
     * @throws InvalidArgumentException
     */
    public function nav(Request $request): Response
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');
        $routes = new Routes($admin);
        return $this->success([
            'list' => $routes->nav()
        ], __('message.common.search.success'));
    }

    public function navSetNoCache(Request $request): Response
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');
        $data = (array)$request->post('data');
        $routes = new Routes($admin);
        $data = collect($data)->mapWithKeys(function (array $array): array {
            return [$array['name'] => $array['no_cache']];
        });
        Cache::forget($routes->cacheKey());
        Cache::store('redis')->forever($routes->cacheKey(), $data);
        return $this->success(__('message.common.update.success'));
    }

    public function navSetAffix(Request $request): Response
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');
        $data = (array)$request->post('data');
        $routes = new Routes($admin);
        $data = collect($data)->mapWithKeys(function (array $array): array {
            return [$array['name'] => $array['affix']];
        });
        Cache::forget($routes->affixKey());
        Cache::store('redis')->forever($routes->affixKey(), $data);
        return $this->success(__('message.common.update.success'));
    }
}
