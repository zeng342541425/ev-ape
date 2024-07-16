<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Questions\CreateRequest;
use App\Http\Requests\Backend\Questions\ListRequest;
use App\Http\Requests\Backend\Questions\IdRequest;
use App\Http\Requests\Backend\Questions\UpdateRequest;
use App\Models\Common\QuestionCategory;
use App\Models\Common\Questions;
use Symfony\Component\HttpFoundation\Response;

class QuestionsController extends Controller
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

        $query = Questions::query()->with('category:id,name');

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $search_words = $param['search_words'];
            $query->where(function ($q) use($search_words) {
                $q->where('title', 'like', "%$search_words%");
                $q->orWhere('answer', 'like', "%$search_words%");
            });
        }

        if (isset($param['status']) && $param['status'] != '') {
            $query->where('status', '=', $param['status']);
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
        $data = $list->items();

        if ($data) {
            foreach($data as $k => $v) {
                $data[$k]['category_name'] = $v['category']['name'] ?? '';
                unset($data[$k]['category']);
            }
        }

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

        $item = Questions::query()->with('category:id,name')->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        $item['category_name'] = $item['category']['name'] ?? '';

        unset($item['category']);
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
            'category_id', 'title', 'answer', 'status', 'sort',
        ]);

        if (!QuestionCategory::query()->where('id', $param['category_id'])->exists()) {
            return $this->error('問題類型不存在');
        }

        $item = Questions::query()->create($param);

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
            'category_id', 'title', 'answer', 'status', 'sort',
        ]);

        $item = Questions::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!QuestionCategory::query()->where('id', $param['category_id'])->exists()) {
            return $this->error('問題類型不存在');
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

        $item = Questions::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
