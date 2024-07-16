<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Parking\ChargingPile;
use App\Models\Parking\ParkingLot;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * 目前充電樁相關統計
     *
     * @return Response
     */
    public function pileNumber(): Response
    {
        $select = [
            DB::raw("count(id) as total"),
            DB::raw("SUM(IF(`status`=0, 1, 0)) AS free_time_number"),
            DB::raw("SUM(IF(`status`=1, 1, 0)) AS busy_number"),
            // DB::raw("SUM(IF(`status`=2, 1, 0)) AS fault_number"),
        ];
        $charging = ChargingPile::query()->select($select)
            ->where('audit_status', 1)
            ->first();

        $res = [
            'total' => 0,
            'remain_number' => 0,
            'busy_number' => 0,
        ];
        if ($charging) {
            $res['total'] = intval($charging['total']);
            $res['busy_number'] = intval($charging['busy_number']);
            $res['remain_number'] = intval($charging['busy_number']) + intval($charging['free_time_number']);
        }

        return $this->success($res);
    }

    /**
     * 目前充電樁相關統計
     *
     * @return Response
     */
    public function orderNumber(): Response
    {
        $select = [
            DB::raw("sum(amount) as total_amount"),
            DB::raw("sum(degree) as total_degree"),
            DB::raw("count(id) as total_number"),
        ];
        $charging = Order::query()->select($select)
            ->where('ending_time', '>=', date('Y-m-d 00:00:00'))
            ->where('ending_time', '<=', date('Y-m-d 23:59:59'))
            ->first();

        $res = [
            'total_number' => 0,
            'total_amount' => 0,
            'total_degree' => 0,
        ];
        if ($charging) {
            $res['total_number'] = intval($charging['total_number']);
            $res['total_degree'] = round($charging['total_degree'], 2);
            $res['total_amount'] = intval($charging['total_amount']);
        }

        return $this->success($res);
    }

    protected function _common(array $param = [], int $type=1)
    {
        $res = $this->_days($param['starting_time'] ?? '', $param['ending_time'] ?? '');

        $r = [];
        if ($res) {
            $starting_time = $res[0];
            $ending_time = substr($res[count($res)-1], 0, 10) . ' 23:59:59';

            foreach($res as $v) {
                $day = substr($v, 0, 10);
                $r[$day] = [
                    'date' => substr($v, 0, 10),
                    'total' => $type == 1 ? 0 : 0.00
                ];
            }

            $select = [
                DB::raw("DATE_FORMAT(created_at,'%Y-%m-%d') AS day"),
            ];

            if ($type == 1) {
                $select[] = DB::raw("sum(amount) as total");
            } else {
                $select[] = DB::raw("sum(degree) as total");
            }

            $query = Order::query()->select($select);

            if (isset($param['parking_lot_id']) && is_numeric($param['parking_lot_id']) && $param['parking_lot_id'] > 0) {
                $query->where('parking_lot_id', '=', $param['parking_lot_id']);
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

            $list = $query->where('ending_time', '>=', $starting_time)
                ->where('ending_time', '<=', $ending_time)
                ->groupBy('day')
                ->get()->toArray();

            if ($list) {
                foreach($list as $v) {
                    if (isset($r[$v['day']])) {

                        $r[$v['day']]['total'] = round($v['total'], 2);
                        if ($type == 1) {
                            $r[$v['day']]['total'] = intval($v['total']);
                        }
                    }
                }
            }
        }

        return array_values($r);
    }

    /**
     * 充電總金額折線圖
     *
     * @param Request $request
     * @return Response
     */
    public function amountLine(Request $request): Response
    {

        $param = $request->all();

        return $this->success(['list' => $this->_common($param)]);
    }

    /**
     * 充電總金額折線圖
     *
     * @param Request $request
     * @return Response
     */
    public function degreeLine(Request $request): Response
    {

        $param = $request->all();

        return $this->success(['list' => $this->_common($param, 2)]);
    }

    protected function _days($start_date='', $end_date=''): array
    {

        if (!$end_date) {
            $end_date = date('Y-m-d');
        }

        if (!$start_date) {
            $start_date = date('Y-m-d 00:00:00', strtotime("-1 month"));
        }

        $start_date = substr($start_date, 0, 10);
        $end_date = substr($end_date, 0, 10);

        if (strtotime($end_date) < strtotime($start_date)) {
            return [];
        }

        // 開始時間戳
        $starting_time = strtotime($start_date . ' 00:00:00');

        $day_list = [$start_date];
        while (true) {
            $day = date('Y-m-d 00:00:00', strtotime("+1 day", $starting_time));
            if (strtotime($day) > strtotime($end_date)) {
                break;
            }
            $day_list[] = $day;
            $starting_time = strtotime(date('Y-m-d 00:00:00', strtotime('+1 day', $starting_time)));
        }

        return $day_list;
    }


}
