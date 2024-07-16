<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\ContactUs\CreateRequest;
use App\Http\Requests\Backend\ContactUs\ListRequest;
use App\Http\Requests\Backend\ContactUs\IdRequest;
use App\Http\Requests\Backend\ContactUs\UpdateRequest;
use App\Models\Common\ContactUs;
use Symfony\Component\HttpFoundation\Response;

class ContactUsController extends Controller
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

        $query = ContactUs::query();

        if (isset($param['status']) && $param['status'] != '') {
            $query->where('status', '=', $param['status']);
        }

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $search_words = $param['search_words'];
            $query->where(function ($q) use($search_words) {
                $q->where('company', 'like', "%$search_words%");
                $q->orWhere('full_name', 'like', "%$search_words%");
                $q->orWhere('job_titles', 'like', "%$search_words%");
                $q->orWhere('telephone', 'like', "%$search_words%");
                $q->orWhere('email', 'like', "%$search_words%");
                $q->orWhere('description', 'like', "%$search_words%");
                $q->orWhere('notes', 'like', "%$search_words%");
            });

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

        $item = ContactUs::query()->find($id);

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
            'status', 'notes',
        ]);

        $item = ContactUs::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!$item->update($param)) {
            return $this->error(__('message.common.update.fail'));
        }

        return $this->success();
    }

}
