<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\InvoiceDonation\CreateRequest;
use App\Http\Requests\Backend\InvoiceDonation\ListRequest;
use App\Http\Requests\Backend\InvoiceDonation\IdRequest;
use App\Http\Requests\Backend\InvoiceDonation\UpdateRequest;
use App\Models\Common\InvoiceDonation;
use App\Models\Order\Order;
use Symfony\Component\HttpFoundation\Response;

class InvoiceDonationController extends Controller
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

        $query = InvoiceDonation::query();

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $search_words = $param['search_words'];
            $query->where(function ($q) use($search_words) {
                $q->where('code', 'like', "%$search_words%");
                $q->orWhere('institution', 'like', "%$search_words%");
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

        $item = InvoiceDonation::query()->find($id);

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
            'code', 'institution', 'status', 'id_card'
        ]);

        if (InvoiceDonation::query()->where('code', $param['code'])->exists()) {
            return $this->error('捐贈碼已存在');
        }

        $item = InvoiceDonation::query()->create($param);

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
            'code', 'institution', 'status', 'id_card'
        ]);

        $item = InvoiceDonation::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (InvoiceDonation::query()->where('code', $param['code'])->whereNot('id', $id)->exists()) {
            return $this->error('捐贈碼已存在');
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

        if (Order::query()->where('invoice_id', $id)->where('invoice_type', 4)->exists()) {
            return $this->error('車主使用該發票，不能刪除');
        }

        $item = InvoiceDonation::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
