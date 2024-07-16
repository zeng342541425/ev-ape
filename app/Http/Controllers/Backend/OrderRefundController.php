<?php
namespace App\Http\Controllers\Backend;

use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\OrderRefund\CreateRequest;
use App\Http\Requests\Backend\OrderRefund\ListRequest;
use App\Http\Requests\Backend\OrderRefund\IdRequest;
use App\Http\Requests\Backend\OrderRefund\UpdateRequest;
use App\Models\Order\OrderRefund;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class OrderRefundController extends Controller
{

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
    public function _list(array $param = [], bool $paginate=true): array
    {

        $query = OrderRefund::query();

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $search_words = $param['search_words'];
            $query->where(function ($q) use($search_words) {
                $q->where('order_number', 'like', "%$search_words%");
                $q->orWhere('pile_no', 'like', "%$search_words%");
                $q->orWhere('parking_lot_name', 'like', "%$search_words%");
                $q->orWhere('phone', 'like', "%$search_words%");
                $q->orWhere('username', 'like', "%$search_words%");
                $q->orWhere('region_name', 'like', "%$search_words%");
                $q->orWhere('village_name', 'like', "%$search_words%");
            });

        }

        if (isset($param['type']) && is_numeric($param['type']) && $param['type'] > 0) {
            $query->where('type', '=', $param['type']);
        }
        if (isset($param['status']) && is_numeric($param['status']) && $param['status'] > 0) {
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

        $query->orderByDesc('created_at');

        if ($paginate) {
            $list = $query->paginate($param['limit']);

            return [
                'list' => $list->items(),
                'total' => $list->total()
            ];
        } else {
            return $query->get()->toArray();
        }

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

        $item = OrderRefund::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        return $this->success([
            'item' => $item
        ]);
    }

    /**
     * 預約列表匯出
     *
     * @param \App\Http\Requests\Backend\Order\ListRequest $request
     * @return BinaryFileResponse
     */
    public function export(Request $request): BinaryFileResponse
    {
        $headings = [
            '訂單ID', '交易編號', '支付單號', '會員帳號', '會員姓名', '站點區域', '充電站名稱', '充電樁編號', '充電日期', '充電時段',
            '充電度數(kWh)', '充電時長(分鐘)', '充電費用(新台幣)', '退款類型', '退款金額', '退款編號', '退款狀態', '退款時間', '備註'
        ];

        $param = $request->all();

        $list = $this->_list($param, false);

        $data = [];
        if ($list) {
            $status_map = [
                1 => '退款中',
                2 => '已退款',
                3 => '退款失敗',
            ];
            $type_map = [
                1 => '全額退款',
                2 => '部分退款',
            ];
            foreach($list as $v) {
                $data[] = [
                    $v['order_id'],
                    $v['order_number'],
                    $v['rec_trade_id'],
                    $v['phone'],
                    $v['username'],
                    $v['region_name'] . $v['village_name'],
                    $v['parking_lot_name'],
                    $v['pile_no'],
                    $v['trade_date'],
                    $v['starting_time'] . $v['ending_time'],
                    $v['degree'],
                    $v['duration'],
                    $v['amount'],
                    $type_map[$v['type']] ?? '',
                    $v['refund_amount'],
                    $v['refund_id'],
                    $status_map[$v['status']] ?? '',
                    $v['created_at'],
                    $v['notes'],
                ];
            }
        }

        return Excel::download(new BaseExport($data, $headings), '退款紀錄報表.xlsx');

    }

}
