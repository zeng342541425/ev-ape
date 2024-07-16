<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\AppointmentReason\CreateRequest;
use App\Http\Requests\Backend\AppointmentReason\ListRequest;
use App\Http\Requests\Backend\AppointmentReason\IdRequest;
use App\Http\Requests\Backend\AppointmentReason\UpdateRequest;
use App\Models\Common\AppointmentReason;
use Symfony\Component\HttpFoundation\Response;

class AppointmentReasonController extends Controller
{
    /**
     * 列表
     *
     * @param ListRequest $request
     * @return Response
     */
    public function list(ListRequest $request): Response
    {
        $param = $request->all();

        $query = AppointmentReason::query();

        if (isset($param['title']) && $param['title'] != '') {
            $query->like('title', $param['title']);
        }

        if (!empty($param['starting_time'])) {
            $starting_time = substr($param['starting_time'], 0, 10) . ' 00:00:00';
            $query->where('created_at', '>=', $starting_time);
        }

        if (!empty($param['ending_time'])) {
            $ending_time = substr($param['ending_time'], 0, 10) . ' 23:59:59';
            $query->where('created_at', '<=', $ending_time);
        }

        $query->orderBy($param['sort'] ?: 'id', $param['order'] ?: 'desc');

        $list = $query->paginate($param['limit'] ?? 10);

        return $this->success([
            'list' => $list->items(),
            'total' => $list->total()
        ]);
    }

    /**
     * 詳情
     *
     * @param IdRequest $request
     * @return Response
     */
    public function detail(IdRequest $request): Response
    {
        $id = $request->post('id');

        $item = AppointmentReason::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        return $this->success([
            'item' => $item
        ]);
    }

    /**
     * 創建
     *
     * @param CreateRequest $request
     * @return Response
     */
    public function create(CreateRequest $request): Response
    {
        $param = $request->only([
            'title',
        ]);

        if (AppointmentReason::query()->where('title', $param['title'])->exists()) {
            return $this->error('取消原因已經存在');
        }

        $item = AppointmentReason::query()->create($param);

        return $this->success();
    }

    /**
     * 更新
     *
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(UpdateRequest $request): Response
    {
        $id = $request->post('id');
        $param = $request->only([
            'title',
        ]);

        $item = AppointmentReason::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (AppointmentReason::query()->where('title', $param['title'])->whereNot('id', $id)->exists()) {
            return $this->error('取消原因已經存在');
        }

        if (!$item->update($param)) {
            return $this->error(__('message.common.update.fail'));
        }

        return $this->success();
    }

    /**
     * 刪除
     *
     * @param IdRequest $request
     * @return Response
     */
    public function delete(IdRequest $request): Response
    {
        $id = $request->post('id');

        $item = AppointmentReason::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
