<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\FaultCategories\CreateRequest;
use App\Http\Requests\Backend\FaultCategories\ListRequest;
use App\Http\Requests\Backend\FaultCategories\IdRequest;
use App\Http\Requests\Backend\FaultCategories\UpdateRequest;
use App\Models\Common\FaultCategories;
use Symfony\Component\HttpFoundation\Response;

class FaultCategoriesController extends Controller
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

        $query = FaultCategories::query();


        $query->orderByDesc('created_at');

        $list = $query->paginate($param['limit'] ?? 10);

        return $this->success([
            'list' => $list->items(),
            'total' => $list->total()
        ]);
    }

    /**
     * 所有列表
     *
     * @return Response
     */
    public function all(): Response
    {

        $list = FaultCategories::query()->get();

        return $this->success([
            'list' => $list
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

        $item = FaultCategories::query()->find($id);

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
            'name',
        ]);

        if (FaultCategories::query()->where('name', $param['name'])->exists()) {
            return $this->error('問題類型名稱已存在');
        }

        $item = FaultCategories::query()->create($param);
        if (!$item) {
            return $this->error();
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
            'name',
        ]);

        $item = FaultCategories::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (FaultCategories::query()->where('name', $param['name'])->whereNot('id', $id)->exists()) {
            return $this->error('問題類型名稱已存在');
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

        $item = FaultCategories::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
