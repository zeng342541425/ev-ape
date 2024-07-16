<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Brands\CreateRequest;
use App\Http\Requests\Backend\Brands\ListRequest;
use App\Http\Requests\Backend\Brands\IdRequest;
use App\Http\Requests\Backend\Brands\UpdateRequest;
use App\Models\Common\DiningHotel;
use App\Models\Common\DiningHotelType;
use App\Models\Common\EmailNotices;
use App\Models\Order\Order;
use App\Models\Parking\Brand;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailNoticesController extends Controller
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

        $query = EmailNotices::query();


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

        $item = EmailNotices::query()->find($id);

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
            'title','content'
        ]);


        $item = EmailNotices::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!$item->update($param)) {
            return $this->error(__('message.common.update.fail'));
        }

        return $this->success();
    }
}
