<?php
namespace App\Http\Controllers\Backend;

use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Order\ListRequest;
use App\Http\Requests\Backend\Order\IdRequest;
use App\Http\Requests\Backend\Order\SaveRequest;
use App\Http\Requests\Backend\Order\UpdateRequest;
use App\Jobs\RegularPushJob;
use App\Mail\Notice;
use App\Models\Order\Order;
use App\Models\Order\OrderRefund;
use App\Models\Parking\ParkingLot;
use App\Services\Common\TapPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OrderController extends Controller
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
        $data = $this->_list($param);

        return $this->success($data);
    }

    /**
     * 列表
     *
     * @param array $param
     * @param bool $paginate
     * @return array
     */
    protected function _list(array $param, bool $paginate=true): array
    {

        $query = Order::query()->with(['parking' => function($q) {
            $q->with(['region', 'village']);
        }]);

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $search_words = $param['search_words'];
            $query->where(function ($q) use($search_words) {
                $q->where('pile_no', 'like', "%$search_words%");
                $q->orWhere('phone', 'like', "%$search_words%");
                $q->orWhere('username', 'like', "%$search_words%");
                $q->orWhere('invoice_number', 'like', "%$search_words%");
                $q->orWhere('order_number', 'like', "%$search_words%");
                $q->orWhere('rec_trade_id', 'like', "%$search_words%");
                $q->orWhere('brand_name', 'like', "%$search_words%");
                $q->orWhere('notes', 'like', "%$search_words%");
            });

        }

        if (!empty($param['trade_starting_time'])) {
            $starting_time = substr($param['trade_starting_time'], 0, 10);
            $query->where('trade_date', '>=', $starting_time);
        }

        if (!empty($param['trade_ending_time'])) {
            $ending_time = substr($param['trade_ending_time'], 0, 10);
            $query->where('trade_date', '<=', $ending_time);
        }

        if (isset($param['user_id']) && is_numeric($param['user_id']) && $param['user_id'] > 0) {
            $query->where('user_id', $param['user_id']);
        }

        if (isset($param['region_id']) && is_numeric($param['region_id']) && $param['region_id'] > 0
            && isset($param['village_id']) && is_numeric($param['village_id'])) {
            $model = ParkingLot::query()->select('id')->where('region_id', $param['region_id']);
            if ($param['village_id'] > 0) {
                $model->where('village_id', $param['village_id']);
            }
            $id_list = $model->get()->toArray();

            $ids = [0];
            if ($id_list) {
                $ids = array_column($id_list, 'id');
            }
            $query->whereIn('parking_lot_id', $ids);

        }

        if (isset($param['status']) && is_numeric($param['status'])) {
            $query->where('status', '=', $param['status']);
        }

        if (isset($param['parking_lot_id']) && is_numeric($param['parking_lot_id']) && $param['parking_lot_id'] > 0) {
            $query->where('parking_lot_id', '=', $param['parking_lot_id']);
        }

        if (isset($param['pile_id']) && is_numeric($param['pile_id']) && $param['pile_id'] > 0) {
            $query->where('pile_id', '=', $param['pile_id']);
        }

        $query->orderByDesc('created_at');

        if ($paginate) {
            $list = $query->with(['order_refund' => function($q) {
                $q->whereNot('status', 3)->select('order_id', 'refund_amount','type');
            }])->paginate($param['limit']);

            $data = $list->items();
            $data = $this->getData($data);

            return [
                'list' => $data,
                'total' => $list->total()
            ];
        } else {
            $data = $query->get()->toArray();
            return $this->getData($data);
        }

    }

    /**
     * 預約列表匯出
     *
     * @param ListRequest $request
     * @return BinaryFileResponse
     */
    public function export(Request $request): BinaryFileResponse
    {
        $headings = [
            '訂單ID', '交易編號', '支付單號', '支付時間', '會員帳號', '會員姓名', '站點區域', '充電站名稱', '車用品牌', '充電樁編號',
            '充電日期', '充電時段', '充電度數(kWh)', '充電時長(分鐘)', '充電費用(新台幣)', '付款狀態', '評分', '備註'
        ];

        $param = $request->all();

        $list = $this->_list($param, false);

        $data = [];
        if ($list) {
            $status_map = [
                0 => '未支付',
                1 => '無需支付',
                2 => '已支付',
                3 => '支付失敗',
            ];
            foreach($list as $v) {
                $data[] = [
                    $v['id'],
                    $v['order_number'],
                    $v['rec_trade_id'],
                    $v['payment_time'],
                    $v['phone'],
                    $v['username'],
                    $v['region_name'] . $v['village_name'],
                    $v['parking_lot_name'],
                    $v['brand_name'],
                    $v['pile_no'],
                    $v['trade_date'],
                    $v['starting_time'] . $v['ending_time'],
                    $v['degree'],
                    $v['duration'],
                    $v['amount'],
                    $status_map[$v['status']] ?? '',
                    $v['star'] == 0 ? '-' : strval($v['star']),
                    $v['notes']
                ];
            }
        }

        return Excel::download(new BaseExport($data, $headings), '充電繳費紀錄報表.xlsx');

    }

    /**
     * 詳情
     *
     * @param int $id
     * @return array
     */
    protected function _detail(int $id=0): array
    {

        $item = Order::query()->with(['parking' => function($q) {
            $q->with(['region', 'village']);
        }])->find($id);

        if (!$item) {
            return [];
        }


        return $item->toArray();

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

        $item = $this->_detail($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        $item['region_name'] = $item['parking']['region']['name'];
        $item['village_name'] = $item['parking']['village']['name'];

        unset($item['parking']);

        return $this->success([
            'item' => $item
        ]);
    }

    /**
     * 詳情
     *
     * @param SaveRequest $request
     * @return Response
     */
    public function save(SaveRequest $request): Response
    {
        $id = $request->post('id');
        $data = $request->only(['notes']);

        $item = Order::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!Order::query()->where('id', $id)->update($data)) {
            return $this->error();
        }

        return $this->success();
    }

    /**
     * 退款
     *
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(UpdateRequest $request): Response
    {
        $id = $request->post('id');
        $type = $request->post('type');
        $refund_amount = $request->post('refund_amount');
        $notes = $request->post('notes');

        $item = $this->_detail($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if ($item['status'] != 2 || strtotime($item['ending_time']) + 90 * 24 * 60 * 60 <= time()) {
            return $this->error();
        }

        $item['region_id'] = $item['parking']['region_id'];
        $item['region_name'] = $item['parking']['region']['name'];
        $item['village_name'] = $item['parking']['village']['name'];
        $item['village_id'] = $item['parking']['village_id'];

        unset($item['parking']);

        $key = 'order_partial_refund';
        $replace = [
            'order_no' => $item['order_number'],
            'amount' => $item['amount'],
        ];
        $refund_data = ['rec_trade_id' => $item['rec_trade_id']];
        if ($type == 1) {
            $key = 'order_full_refund';
            $refund_amount = $item['amount'];

        } else {
            $refund_data['amount'] = intval($refund_amount);
            $replace['amount'] = intval($refund_amount);
        }

        $data = $item;
        unset($data['id']);
        $data['order_id'] = $id;
        $data['refund_amount'] = $refund_amount;
        $data['type'] = $type;
        $data['status'] = 1;
        $data['admin_id'] = Auth::id();
        $data['notes'] = $notes;

        // 判斷退款金額是否組溝通
        $total_refund_amount = OrderRefund::query()->where('order_id', $id)->whereNot('status', 3)->sum('refund_amount');
        if ($total_refund_amount && ($refund_amount + $total_refund_amount) > $data['amount']) {
            return $this->error('退款總金額大於充電費用');
        }

        unset($data['charging_status']);
        unset($data['invoice_number']);
        $rid = OrderRefund::query()->insertGetId($data);
        if (!$rid) {
            return $this->error('操作失敗');
        }

        // 隊列退款tappay
        $refund_res = (new TapPayService())->refund($refund_data);
        if ($refund_res) {
            $refund_id = $refund_res['refund_id'] ?? '';
            $r = OrderRefund::query()->where('id', $rid)->update([
                'status' => 2,
                'refund_id' => $refund_id
            ]);
            if (!$r) {
                Log::info('退款成功修改狀態失敗', ['id' => $rid, 'refund_id' => $refund_id]);
            }

            // 推播
            RegularPushJob::dispatch($item['user_id'], $key, $replace);



            $arr = [
                'username' => $item['name'],
                'amount' => $refund_amount,
                'order_no' => $item['order_number'],
                'pile_no' => $item['pile_no'],
                'degree' => $item['degree'],
                'duration' => (integer)$item['duration']/60,
            ];

            Mail::to($item['email'])->send(new Notice($key, $arr));

        } else {
            $r = OrderRefund::query()->where('id', $rid)->update([
                'status' => 3,
            ]);

            if (!$r) {
                Log::info('退款失敗修改狀態失敗', ['id' => $rid]);
            }

        }

        return $this->success();

    }

    /**
     * @param array $data
     * @return array
     */
    protected function getData(array $data): array
    {
        if ($data) {
            foreach ($data as $k => $v) {

                $data[$k]['region_name'] = $v['parking']['region']['name'] ?? '';
                $data[$k]['village_name'] = $v['parking']['village']['name'] ?? '';
                // $data[$k]['images'] = json_decode($v['images'], true);

                $refund_amount = $refund_type = 0;
                if (isset($v['order_refund']) && !empty($v['order_refund'])) {

                    $refund_amount = $v['order_refund']['refund_amount'];

                    $refund_type = $v['order_refund']['type'];
//                    foreach($v['order_refund'] as $vv) {
//                        $refund_amount += $vv['refund_amount'];
//                    }
                }
                $data[$k]['refund_amount'] = $refund_amount;
                $data[$k]['refund_type'] = $refund_type;

                $data[$k]['refund_able'] = 1;
                if ($refund_amount > 0 || ($v['status'] == 2 && strtotime($v['ending_time']) + 90 * 24 * 60 * 60 <= time())) {
                    $data[$k]['refund_able'] = 0;
                }

                // if (empty($v['invoice_number'])) {
                //     $data[$k]['refund_able'] = 0;
                // }

                unset($data[$k]['parking']);
                unset($data[$k]['invoice_info']);
                unset($data[$k]['order_refund']);
            }
        }
        return $data;
    }

}
