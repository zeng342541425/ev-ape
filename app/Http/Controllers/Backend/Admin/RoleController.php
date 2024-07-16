<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Admin\Role\CreateRequest;
use App\Http\Requests\Backend\Admin\Role\ListRequest;
use App\Http\Requests\Backend\Admin\Role\SyncPermissionsRequest;
use App\Http\Requests\Backend\Admin\Role\UpdateRequest;
use App\Http\Requests\Common\IdRequest;
use App\Models\Backend\Admin\ModelHasRoles;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    /**
     * 獲取角色列表
     * @param ListRequest $request
     * @return Response
     */
    public function list(ListRequest $request): Response
    {
        $param = $request->all();

        $query = Role::notSuperAdmin();

        if (isset($param['name']) && $param['name'] != '') {
            $query->where('name', 'like', '%' . $param['name'] . '%');
        }
        if (!empty($param['status'])) {
            $query->where('status', $param['status']);
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

        return $this->success([
            'list' => $list->items(),
            'total' => $list->total()
        ]);
    }

    /**
     * 獲取角色詳情
     * @param IdRequest $request
     * @return Response
     */
    public function detail(IdRequest $request): Response
    {
        $id = $request->post('id');
        $role = Role::query()->find($id);
        if (!$role) {
            return $this->error(__('message.data_not_found'));
        }
        $role->append([
            'permission_ids'
        ])->makeHidden([
            'permissions'
        ]);

        return $this->success([
            'item' => $role
        ]);
    }

    /**
     * 獲取全部角色列表
     * @param Request $request
     * @return Response
     */
    public function allRoles(Request $request): Response
    {
        $list = Role::notSuperAdmin()->get();
        return $this->success([
            'list' => $list
        ]);
    }

    /**
     * 創建角色
     *
     * @param CreateRequest $request
     * @return Response
     */
    public function create(CreateRequest $request): Response
    {
        $param = $request->only([
            'full_name', 'status'
        ]);

        $param['name'] = $param['full_name'];

        if (Role::name($param['name'])->first()) {
            return $this->error(__('message.role.name_exist'));
        }
        $item = Role::create($param);
        if (!$item) {
            return $this->error(__(__('message.common.create.fail')));
        }

        return $this->success([
            'item' => $item
        ], __('message.common.create.success'));
    }

    /**
     * 更新角色
     *
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(UpdateRequest $request): Response
    {
        $id = $request->post('id');
        $param = $request->only([
            'full_name', 'status'
        ]);

        $param['name'] = $param['full_name'];

        $item = Role::notSuperAdmin()->find($id);
        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        // 檢測角色標識是否已存在
        if ($param['name'] != $item->name && Role::name($param['name'])->first()) {
            return $this->error(__('message.role.name_exist'));
        }

        $res = $item->update($param);
        if (!$res) {
            return $this->error(__(__('message.common.update.fail')));
        }

        return $this->success([
            'item' => $item
        ], __('message.common.update.success'));

    }

    /**
     * 刪除角色
     * @param IdRequest $request
     * @return Response
     */
    public function delete(IdRequest $request): Response
    {
        $id = $request->post('id');

        $item = Role::notSuperAdmin()->find($id);
        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        // 檢測 是否有 管理員使用此權限
        $used_admins = ModelHasRoles::query()->where('role_id', $id)->first();
        if ($used_admins) {
            return $this->error(__('message.role.have_admins_used'));
        }

        $item->syncPermissions([]);
        $item->delete();

        return $this->success(msg: __('message.common.delete.success'));
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

        $item = Role::notSuperAdmin()->find($id);
        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        // 查詢存在的權限
        if (!empty($permission_ids)) {
            $permission_ids = array_unique($permission_ids);
            $permission_ids = Permission::query()->whereIn('id', $permission_ids)->pluck('id')->toArray();
        }

        // 設置權限
        $item->syncPermissions($permission_ids);

        $item->append([
            'permission_ids'
        ])->makeHidden([
            'permissions'
        ]);

        // 操作記錄
        activity()
            ->useLog('role')
            ->performedOn($item)
            ->causedBy($request->user())
            ->withProperties(compact('id', 'permission_ids'))
            ->log('update permissions');

        return $this->success([
            'item' => $item
        ], __('message.common.update.success'));
    }
}
