<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FirebasePush\CreateRequest;
use App\Http\Requests\Backend\FirebasePush\ListRequest;
use App\Http\Requests\Backend\FirebasePush\IdRequest;
use App\Http\Requests\Backend\FirebasePush\UpdateRequest;
use App\Jobs\NoticePushJob;
use App\Models\Common\FirebasePush;
use App\Models\Common\UserNotice;
use App\Models\Frontend\User\User;
use Symfony\Component\HttpFoundation\Response;

class FirebasePushController extends Controller
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

        $query = FirebasePush::query();

        if (!empty($param['starting_time'])) {
            $starting_time = substr($param['starting_time'], 0, 10) . ' 00:00:00';
            $query->where('created_at', '>=', $starting_time);
        }

        if (!empty($param['ending_time'])) {
            $ending_time = substr($param['ending_time'], 0, 10) . ' 23:59:59';
            $query->where('created_at', '<=', $ending_time);
        }

        if (isset($param['status']) && is_numeric($param['status']) && $param['status'] >= 0) {
            $query->where('status', '=', $param['status']);
        }

        $query->orderByDesc('created_at');

        $list = $query->paginate($param['limit']);

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

        $item = FirebasePush::query()->find($id);

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
            'type', 'title', 'content', 'send_time'
        ]);

        if ($param['type'] == 2 && empty($param['send_time'])) {
            return $this->error('預約發送時間不能爲空');
        }

        if (empty($param['send_time']) || strtotime($param['send_time']) < time()) {
            $param['send_time'] = date('Y-m-d H:i:s');
        }

        $item = FirebasePush::query()->create($param);
        if ($item && $param['type'] == 1) {
            // $user_ids = User::query()->select('id')->where('status', 1)->get()->toArray();
            // $tmp = [];
            // foreach($user_ids as $v) {
            //     $tmp[] = [
            //         'user_id' => $v['id'],
            //         'title' => $param['title'],
            //         'published_at' => $param['send_time'],
            //         'content' => $param['content'],
            //         'brief_introduction' => $param['content'],
            //         'reading' => 0,
            //         'created_at' => date('Y-m-d H:i:s'),
            //         'updated_at' => date('Y-m-d H:i:s'),
            //     ];
            // }
            // UserNotice::query()->insert($tmp);
            FirebasePush::query()->where('id', $item['id'])->update(['status' => 1]);
            NoticePushJob::dispatch($item);
        }

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
            'type', 'title', 'content', 'send_time'
        ]);

        $item = FirebasePush::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if ($param['type'] == 2 && empty($param['send_time'])) {
            return $this->error('預約發送時間不能爲空');
        }

        if (empty($param['send_time']) || strtotime($param['send_time']) < time()) {
            $param['send_time'] = date('Y-m-d H:i:s');
        }

        if ($item['status'] == 1) {
            return $this->error('推播已經發送，不能編輯');
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

        $item = FirebasePush::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if ($item['status'] == 1) {
            return $this->error('推播已經發送，不能刪除');
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
