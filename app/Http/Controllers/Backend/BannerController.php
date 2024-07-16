<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Banner\CreateRequest;
use App\Http\Requests\Backend\Banner\ListRequest;
use App\Http\Requests\Backend\Banner\IdRequest;
use App\Http\Requests\Backend\Banner\UpdateRequest;
use App\Models\Common\Banner;
use Symfony\Component\HttpFoundation\Response;

class BannerController extends Controller
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

        $current_datetime = date('Y-m-d H:i:s');
        Banner::query()
            ->where('ending_time', '<', $current_datetime)
            ->where('status', 1)
            ->update(['status' => 0]);

        $query = Banner::query()->whereRaw(' 1=1 ');

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $search_words = $param['search_words'];
            $query->where(function ($q) use($search_words) {
                $q->where('name', 'like', "%$search_words%");
            });

        }

        if (isset($param['status']) && $param['status'] != '') {
            $query->where('status', '=', $param['status']);
        }

        if (!empty($param['starting_time']) && !empty($param['ending_time'])) {
            $starting_time = substr($param['starting_time'], 0, 10) . ' 00:00:00';
            $ending_time = substr($param['ending_time'], 0, 10) . ' 23:59:59';
            $query->where(function($q) use($starting_time, $ending_time) {
                $q->where('starting_time', '<=', $starting_time);
                $q->orWhere('ending_time', '>=', $ending_time);
            });
        }

        // if (!empty($param['starting_time'])) {
        //     $starting_time = substr($param['starting_time'], 0, 10) . ' 00:00:00';
        //     $query->where('starting_time', '<=', $starting_time);
        // }
        //
        // if (!empty($param['ending_time'])) {
        //     $ending_time = substr($param['ending_time'], 0, 10) . ' 00:00:00';
        //     $query->where('ending_time', '>=', $ending_time);
        // }

        $query->orderBy('sort');

        $list = $query->paginate($param['limit'] ?? 10);

        $data = $list->items();

        return $this->success([
            'list' => $data,
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

        $item = Banner::query()->find($id);

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
            'image_url', 'sort', 'name', 'status', 'starting_time', 'ending_time'
        ]);

        if (strtotime($param['starting_time']) >= strtotime($param['ending_time'])) {
            return $this->error('上架開始時間須小於結束時間');
        }

        $item = Banner::query()->create($param);

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
            'image_url', 'sort', 'name', 'status', 'starting_time', 'ending_time'
        ]);

        if (strtotime($param['starting_time']) >= strtotime($param['ending_time'])) {
            return $this->error('上架開始時間須小於結束時間');
        }

        $item = Banner::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
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

        $item = Banner::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
