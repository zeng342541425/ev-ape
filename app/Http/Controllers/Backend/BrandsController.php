<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Brands\CreateRequest;
use App\Http\Requests\Backend\Brands\ListRequest;
use App\Http\Requests\Backend\Brands\IdRequest;
use App\Http\Requests\Backend\Brands\UpdateRequest;
use App\Models\Order\Order;
use App\Models\Parking\Brand;
use Symfony\Component\HttpFoundation\Response;

class BrandsController extends Controller
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

        $query = Brand::query();

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $query->like('brand_name', $param['search_words']);
        }

        if (!empty($param['starting_time'])) {
            $starting_time = substr($param['starting_time'], 0, 10) . ' 00:00:00';
            $query->where('created_at', '>=', $starting_time);
        }

        if (!empty($param['ending_time'])) {
            $ending_time = substr($param['ending_time'], 0, 10) . ' 23:59:59';
            $query->where('created_at', '<=', $ending_time);
        }

        $query->orderBy('brand_name');

        $list = $query->paginate($param['limit'] ?? 10);

        return $this->success([
            'list' => $list->items(),
            'total' => $list->total(),
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

        $item = Brand::query()->find($id);

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
            'brand_name',
        ]);

        if (Brand::query()->where('brand_name', $param['brand_name'])->exists()) {
            return $this->error('品牌已經存在');
        }

        $item = Brand::query()->create($param);
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
            'brand_name',
        ]);

        if (Brand::query()->where('brand_name', $param['brand_name'])->whereNot('id', $id)->exists()) {
            return $this->error('品牌已經存在');
        }

        $item = Brand::query()->find($id);

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

        $item = Brand::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (Order::query()->where('brand_id', $id)->exists()) {
            return $this->error('有車主使用該品牌');
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
