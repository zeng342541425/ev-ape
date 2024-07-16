<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Admin\Permission\CreateRequest;
use App\Http\Requests\Backend\Admin\Permission\DropRequest;
use App\Http\Requests\Backend\Admin\Permission\ListRequest;
use App\Http\Requests\Backend\Admin\Permission\UpdateRequest;
use App\Http\Requests\Common\IdRequest;
use App\Models\Backend\Admin\Permission;
use App\Models\Backend\Admin\Role;
use App\Util\ArrayTool;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends Controller
{
    /**
     * 詳情
     * @param IdRequest $request
     * @return Response
     */
    public function detail(IdRequest $request): Response
    {
        $id = $request->post('id');
        $permission = Permission::query()->find($id);
        if (!$permission) {
            return $this->error(__('message.data_not_found'));
        }
        return $this->success([
            'item' => $permission
        ]);
    }

    /**
     * 獲取權限列表
     *
     * @param ListRequest $request
     * @return Response
     */
    public function list(ListRequest $request): Response
    {
        $param = $request->all();
        $query = Permission::query();

        if (isset($param['keyword']) && $param['keyword'] != '') {
            $query->where(function ($query) use ($param) {
                $query->where('name', 'like', '%' . $param['keyword'] . '%')
                    ->orWhere('title', 'like', '%' . $param['keyword'] . '%')
                    ->where('path', 'like', '%' . $param['keyword'] . '%');
            });
        }
        if (!empty($param['created_at'])) {
            $query->timeBetween('created_at', $param['created_at']);
        }
        if (!empty($param['hidden'])) {
            $query->where('hidden', $param['hidden']);
        }

        if (!empty($param['sort']) && !empty($param['order'])) {
            $query->orderBy($param['sort'], order_direction($param['order']));
        } else {
            $query->orderByDesc('sort');
        }

        $list = $query->paginate($param['limit']);


        return $this->success([
            'list' => $list->items(),
            'total' => $list->total()
        ]);
    }

    /**
     * 權限列表樹形結構
     * @param Request $request
     * @return Response
     */
    public function tree(Request $request): Response
    {
        $param = $request->all();
        $query = Permission::query();
        // $param['hidden'] = 1;

        if (isset($param['keyword']) && $param['keyword'] != '') {
            $query->where(function ($query) use ($param) {
                $query->where('name', 'like', '%' . $param['keyword'] . '%')
                    ->orWhere('title', 'like', '%' . $param['keyword'] . '%')
                    ->where('path', 'like', '%' . $param['keyword'] . '%');
            });
        }
        if (!empty($param['hidden'])) {
            $query->where('hidden', $param['hidden']);
        }

        $permissions = $query->select(['id', 'pid', 'name', 'title', 'icon', 'active_menu', 'hidden'])
            ->orderByDesc('sort')
            ->get();

        $tree = ArrayTool::setChildrenInParentNew($permissions->toArray());
        $tree = $this->getIsMenuAttribute($tree);
        foreach($tree as $k => $v) {
            if ($v['name'] == 'system') {
                unset($tree[$k]);
                continue;
            }

            if ($v['name'] == 'permission-manage') {
                foreach($v['children'] as $kk => $vv) {
                    if ($vv['name'] == 'permission.permissions') {
                        unset($v['children'][$kk]);
                    }
                }

                $tree[$k]['children'] = array_values($v['children']);
            }
        }

        return $this->success([
            'tree' => array_values($tree)
        ]);
    }

    protected function getIsMenuAttribute(array $list)
    {
        foreach ($list as &$item) {
            $is_menu = 0;
            foreach ($item['children'] as $sub) {
                if ($sub['hidden'] == 2) {
                    $is_menu = 1;
                }
            }

            $item['is_menu'] = $is_menu;
            $item['children'] = $this->getIsMenuAttribute($item['children']);
        }
        return $list;
    }

    /**
     * 更改排序與層級
     *
     * @param DropRequest $request
     * @return Response
     */
    public function drop(DropRequest $request): Response
    {
        $param = $request->all();

        // 拖拽至權限位置
        $drop = Permission::query()->find($param['drop']);
        if (!$drop) {
            return $this->error(__('message.data_not_found'));
        }

        // 正在操作的權限
        $dragging = Permission::query()->find($param['dragging']);
        if (!$dragging) {
            return $this->error(__('message.data_not_found'));
        }

        switch ($param['type']) {
            case 'before':
                $res = $dragging->update(['sort' => $drop->sort + 1, 'pid' => $drop->pid]);
                break;
            case 'inner':
                $res = $dragging->update(['pid' => $drop->id]);
                break;
            case 'after':
                $res = $dragging->update([
                    'sort' => ($drop->sort - 1) < 1 ? 0 : $drop->sort - 1,
                    'pid' => $drop->pid
                ]);
                break;
            default:
        }

        if (!empty($res)) {
            return $this->success([
                'item' => $drop
            ], __('message.common.update.success'));
        }

        return $this->error(__('message.common.update.fail'));
    }

    /**
     * 創建權限
     *
     * @param CreateRequest $request
     * @return Response
     */
    public function create(CreateRequest $request): Response
    {
        $param = $request->only([
            'pid', 'name', 'title', 'icon', 'path', 'component', 'sort', 'hidden', 'active_menu'
        ]);

        // 檢測父級是否存在
        if ($param['pid'] && !Permission::query()->find($param['pid'])) {
            return $this->error(__('message.permission.parent_not_exist'));
        }

        // 檢測權限是否已存在
        if (Permission::name($param['name'])->first()) {
            return $this->error(__('message.permission.name_exist'));
        }

        $item = Permission::create($param);

        Role::syncSuperAdminPermissions();

        return $this->success([
            'item' => $item
        ]);
    }

    /**
     * 更新權限
     *
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(UpdateRequest $request): Response
    {
        $param = $request->only([
            'pid', 'name', 'title', 'icon', 'path', 'component', 'sort', 'hidden', 'active_menu'
        ]);
        $id = $request->post('id');

        $item = Permission::query()->find($id);
        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        // 檢測父級是否存在
        if ($param['pid'] && !Permission::query()->find($param['pid'])) {
            return $this->error(__('message.permission.parent_not_exist'));
        }

        // 檢測權限是否已存在
        if ($param['name'] != $item['name'] && Permission::name($param['name'])->first()) {
            return $this->error(__('message.permission.name_exist'));
        }

        $res = $item->update($param);
        if ($res) {

            Role::syncSuperAdminPermissions();

            return $this->success([
                'item' => $item
            ], __('message.common.update.success'));
        }

        return $this->error(__('message.common.update.fail'));
    }

    /**
     * 刪除權限
     * @param IdRequest $request
     * @return Response
     * @throws Exception
     */
    public function delete(IdRequest $request): Response
    {
        $id = $request->post('id');

        $permission = Permission::query()->find($id);
        if (!$permission) {
            return $this->error(__('message.data_not_found'));
        }

        // 存在子級
        if (Permission::pid($id)->first()) {
            return $this->error(__('message.permission.delete_pid'));
        }

        if (!$permission->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        // 重置已緩存的角色和權限
        (new Permission())->forgetCachedPermissions();

        return $this->success(msg: __('message.common.delete.success'));
    }
}
