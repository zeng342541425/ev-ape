<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Advertisement\CreateRequest;
use App\Http\Requests\Backend\Advertisement\ListRequest;
use App\Http\Requests\Backend\Advertisement\IdRequest;
use App\Http\Requests\Backend\Advertisement\UpdateRequest;
use App\Models\Common\Advertisement;
use Exception;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AdvertisementController extends Controller
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

        $query = Advertisement::query();

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $query->like('name', $param['search_words']);
        }
        if (isset($param['status']) && $param['status'] != '') {
            $query->where('status', '=', $param['status']);
        }

        if (!empty($param['starting_time']) && !empty($param['ending_time'])) {
            $starting_time = substr($param['starting_time'], 0, 10) . ' 00:00:00';
            $ending_time = substr($param['ending_time'], 0, 10) . ' 23:59:59';
            $query->where(function($q) use($starting_time, $ending_time) {
                $q->where(function($qq) use($starting_time) {
                    $qq->where('starting_time', '<=', $starting_time);
                    $qq->Where('ending_time', '>=', $starting_time);
                });
                $q->orWhere(function($qqq) use($ending_time) {
                    $qqq->where('starting_time', '<=', $ending_time);
                    $qqq->Where('ending_time', '>=', $ending_time);
                });

            });

        }

        $query->orderBy($param['sort'] ?: 'id', $param['order'] ?: 'desc');

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

        $item = Advertisement::query()->find($id);

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
            'image_url', 'name', 'status', 'link_type', 'link_value', 'starting_time', 'ending_time'
        ]);

        if ($param['link_type'] == 1) {
            $request->validate([
                'link_value' => ['required', 'in:appointment,booking,center']
            ]);
        } else {
            $request->validate([
                'link_value' => ['required', 'url']
            ]);
        }

        if ($param['status'] == 1) {
            $r = $this->checkRepeatTime($param['starting_time'], $param['ending_time']);
            if (!$r) {
                return $this->error('已與其他蓋板廣告上架下時間重複，請檢查');
            }
        }

        DB::beginTransaction();
        try {
            $item = Advertisement::query()->create($param);
            if (!$item) {
                throw new Exception(__('message.common.create.fail'));
            }

            // if ($param['status'] == 1) {
            //     Advertisement::query()->whereNot('id', $item['id'])->update(['status' => 0]);
            // }

            DB::commit();
            return $this->success();

        } catch (Throwable $e) {

            DB::rollBack();
            return $this->error($e->getMessage());
        }
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
            'image_url', 'name', 'status', 'link_type', 'link_value', 'starting_time', 'ending_time'
        ]);

        $item = Advertisement::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if ($param['status'] == 1) {
            $r = $this->checkRepeatTime($param['starting_time'], $param['ending_time'], $id);
            if (!$r) {
                return $this->error('已與其他蓋板廣告上架下時間重複，請檢查');
            }
        }

        DB::beginTransaction();
        try {

            if (!$item->update($param)) {
                return $this->error(__('message.common.update.fail'));
            }

            // if ($param['status'] == 1) {
            //     Advertisement::query()->whereNot('id', $id)->update(['status' => 0]);
            // }

            DB::commit();
            return $this->success();

        } catch (Throwable $e) {

            DB::rollBack();
            return $this->error($e->getMessage());
        }

    }

    // 檢查時間是否重複
    public function checkRepeatTime($starting_time, $ending_time, $id = 0): bool
    {
        // 舉例： 提交的活動時間為2022-04-11 - 2022-04-29
        // 重複1：2022-04-01 - 2022-04-13  提交的開始時間被包含，在左側
        // 重複2：2022-04-01 - 2022-04-30  提交的開始和結束時間都被包含，在中間
        // 重複3：2022-04-12 - 2022-04-30  提交的結束時間被包含，在右側
        $model = Advertisement::query()->where('status', 1);

        if ($id) {
            $model->where('id', '!=', $id);
        }

        $res = $model->select(['starting_time', 'ending_time'])->get()->toArray();

        if($res) {
            $submit_begin_time = strtotime($starting_time);
            $submit_end_time = strtotime($ending_time);
            foreach($res as $v) {
                $begin_time = strtotime($v['starting_time']);
                $end_time = strtotime($v['ending_time']);

                // 檢查重複1：
                if($begin_time <= $submit_begin_time && $end_time >= $submit_begin_time) {
                    return false;
                }

                // 檢查重複3：
                if($begin_time <= $submit_end_time && $end_time >= $submit_end_time) {
                    return false;
                }

                // 檢查重複2：
                if($begin_time <= $submit_begin_time && $end_time >= $submit_end_time) {
                    return false;
                }

            }
        }

        return true;

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

        $item = Advertisement::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
