<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Brands\CreateRequest;
use App\Http\Requests\Backend\Brands\ListRequest;
use App\Http\Requests\Backend\Brands\IdRequest;
use App\Http\Requests\Backend\Brands\UpdateRequest;
use App\Models\Common\DiningHotel;
use App\Models\Common\DiningHotelType;
use App\Models\Order\Order;
use App\Models\Parking\Brand;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DiningHotelTypeController extends Controller
{
    /**
     * 列表
     *
     * @param ListRequest $request
     * @return Response
     */
    public function list(Request $request): Response
    {
        $param = $request->all();

        $query = DiningHotelType::query();

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $query->like('name', $param['search_words']);
        }

        $list = $query->paginate($param['limit'] ?? 10);

        return $this->success([
            'list' => $list->items(),
            'total' => $list->total(),
        ]);
    }

    public function all(): Response
    {


        $list = DiningHotelType::query()->get();

        return $this->success([
            'list' => $list,
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

        $item = DiningHotelType::query()->find($id);

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

        if (DiningHotelType::query()->where('name', $param['name'])->exists()) {
            return $this->error('類型已經存在');
        }

        $item = DiningHotelType::query()->create($param);
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

        if (DiningHotelType::query()->where('name', $param['name'])->whereNot('id', $id)->exists()) {
            return $this->error('品牌已經存在');
        }

        $item = DiningHotelType::query()->find($id);

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

        $item = DiningHotelType::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (DiningHotel::query()->where('type_id', $id)->exists()) {
            return $this->error('有餐廳使用該類型');
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
