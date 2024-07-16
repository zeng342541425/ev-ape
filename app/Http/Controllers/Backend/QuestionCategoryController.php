<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\QuestionCategory\CreateRequest;
use App\Http\Requests\Backend\QuestionCategory\ListRequest;
use App\Http\Requests\Backend\QuestionCategory\IdRequest;
use App\Http\Requests\Backend\QuestionCategory\UpdateRequest;
use App\Models\Common\QuestionCategory;
use App\Models\Common\Questions;
use Symfony\Component\HttpFoundation\Response;

class QuestionCategoryController extends Controller
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

        $query = QuestionCategory::query();

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $query->like('name', $param['search_words']);
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

        $item = QuestionCategory::query()->find($id);

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

        if (QuestionCategory::query()->where('name', $param['name'])->exists()) {
            return $this->error('問題類型名稱已存在');
        }

        $item = QuestionCategory::query()->create($param);

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

        $item = QuestionCategory::query()->find($id);

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

        $item = QuestionCategory::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (Questions::query()->where('category_id', $id)->exists()) {
            return $this->error('問題管理使用該問題分類');
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
