<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FirebaseNotice\CreateRequest;
use App\Http\Requests\Backend\FirebaseNotice\ListRequest;
use App\Http\Requests\Backend\FirebaseNotice\IdRequest;
use App\Http\Requests\Backend\FirebaseNotice\UpdateRequest;
use App\Models\Common\FirebaseNotice;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class FirebaseNoticeController extends Controller
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

        $select = [
            'id', 'title', 'content', 'created_at', 'project', 'able', 'admin_name'
        ];
        $query = FirebaseNotice::query()->select($select);

        if (!empty($param['starting_time'])) {
            $starting_time = substr($param['starting_time'], 0, 10) . ' 00:00:00';
            $query->where('created_at', '>=', $starting_time);
        }

        if (!empty($param['ending_time'])) {
            $ending_time = substr($param['ending_time'], 0, 10) . ' 23:59:59';
            $query->where('created_at', '<=', $ending_time);
        }

        $query->orderBy('id');

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

        $select = [
            'id', 'title', 'content', 'created_at', 'project', 'able', 'admin_name'
        ];
        $item = FirebaseNotice::query()->select($select)->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        return $this->success([
            'item' => $item
        ]);
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
            'title', 'content',
        ]);

        $item = FirebaseNotice::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if ($item['able'] == 0) {
            return $this->error('無法編輯');
        }

        $admin = Auth::user();
        $param['admin_id'] = $admin['id'];
        $param['admin_name'] = $admin['name'];

        if (!$item->update($param)) {
            return $this->error(__('message.common.update.fail'));
        }

        return $this->success();
    }

}
