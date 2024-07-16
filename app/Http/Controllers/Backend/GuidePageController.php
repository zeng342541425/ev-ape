<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\GuidePage\CreateRequest;
use App\Http\Requests\Backend\GuidePage\IdRequest;
use App\Http\Requests\Backend\GuidePage\UpdateRequest;
use App\Models\Common\GuidePage;
use Symfony\Component\HttpFoundation\Response;

class GuidePageController extends Controller
{
    /**
     * 列表
     *
     * @return Response
     */
    public function list(): Response
    {

        $list = GuidePage::query()->orderBy('sort')->get()->toArray();

        return $this->success([
            'list' => $list,
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
            'image_url', 'sort',
        ]);

        $item = GuidePage::query()->create($param);

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
            'image_url', 'sort',
        ]);

        $item = GuidePage::query()->find($id);

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

        $item = GuidePage::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
