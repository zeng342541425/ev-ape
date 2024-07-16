<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Message\CreateRequest;
use App\Http\Requests\Backend\Message\ListRequest;
use App\Http\Requests\Backend\Message\IdRequest;
use App\Http\Requests\Backend\Message\UpdateRequest;
use App\Jobs\MessageFirebaseJob;
use App\Models\Common\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class MessageController extends Controller
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

        $query = Message::query();

        if (!empty($param['starting_time'])) {
            $starting_time = substr($param['starting_time'], 0, 10) . ' 00:00:00';
            $query->where('published_at', '>=', $starting_time);
        }

        if (!empty($param['ending_time'])) {
            $ending_time = substr($param['ending_time'], 0, 10) . ' 23:59:59';
            $query->where('published_at', '<=', $ending_time);
        }

        // if (isset($param['status']) && is_numeric($param['status']) && $param['status'] >= 0) {
        //     $query->where('status', '=', $param['status']);
        // }

        $query->orderByDesc('published_at');

        $list = $query->paginate($param['limit']);

        $data = $list->items();
        if ($data) {
            foreach($data as $k => $v) {
                if ($v['status'] == 0 && strtotime($v['published_at']) <= time()) {
                    $data[$k]['status'] = 1;
                }

            }
        }

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

        $item = Message::query()->find($id);

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
            'title', 'published_at', 'content', 'brief_introduction', 'type', 'send_type'
        ]);

        if ($param['send_type'] == 2 && empty($param['published_at'])) {
            return $this->error('預約發送時間不能爲空');
        }

        if (empty($param['published_at']) || strtotime($param['published_at']) < time()) {
            $param['published_at'] = date('Y-m-d H:i:s');
        }

        // 如果立即發送
        if ($param['send_type'] == 1) {
            $param['status'] = 1;
            $param['published_at'] = date('Y-m-d H:i:s');
        }

        $admin = Auth::user();
        $param['admin_id'] = $admin['id'];
        $param['admin_name'] = $admin['name'];
        $param['admin_username'] = $admin['username'];

        $item = Message::query()->create($param);

        if ($item['type'] !== 2 && $item['status'] == 1){
            MessageFirebaseJob::dispatch($item);
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
            'title', 'published_at', 'content', 'send_type'
        ]);

        $item = Message::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if ($param['send_type'] == 2 && empty($param['published_at'])) {
            return $this->error('預約發送時間不能爲空');
        }

        if (empty($param['published_at']) || strtotime($param['published_at']) < time()) {
            $param['published_at'] = date('Y-m-d H:i:s');
        }

        // 如果立即發送
        if ($item['status'] == 0 && $param['send_type'] == 1) {
            $param['status'] = 1;
            $param['published_at'] = date('Y-m-d H:i:s');
        }

        $admin = Auth::user();
        $param['admin_id'] = $admin['id'];
        $param['admin_name'] = $admin['name'];
        $param['admin_username'] = $admin['username'];

        if ($item['type'] !== 2 && $param['status'] == 1 && $item['status'] !==  $param['status']){
            MessageFirebaseJob::dispatch($item);
        }

        if (!$item->update($param)) {
            return $this->error(__('message.common.update.fail'));
        }



        return $this->success();
    }

    // 取消發佈
    public function cancel(IdRequest $request): Response
    {
        $id = $request->post('id');

        $item = Message::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        $param['status'] = 2;

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

        $item = Message::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }

    // 預覽
    public function preview(Request $request): Response
    {

        $content = $request->get('content');
        $title = $request->get('title', '預覽');

        // 1：官網預覽；2：手機預覽
        $type = $request->get('type', 1);

        $ex['preview_content'] = View::make('common.template', ['template_content' => $content, 'template_title' => $title])->render();
        if ($type == 2) {
            $ex['preview_content'] = View::make('common.app_template', ['template_content' => $content, 'template_title' => $title])->render();
        }


        return $this->success(['info' => $ex ?: new \stdClass()]);

    }
}
