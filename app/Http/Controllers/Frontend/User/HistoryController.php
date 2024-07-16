<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\User\My\HistoryRequest;
use App\Models\Common\InvoiceDonation;
use App\Models\Common\OrderCard;
use App\Models\Order\Order;
use App\Models\Order\OrderRefund;
use App\Models\User\CreditCard;
use App\Models\User\Invoice;
use App\Services\Common\InvoiceService;
use App\Services\Common\PaymentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;


class HistoryController extends BaseController
{

    /**
     * 統計數據
     *
     * @param Request $request
     * @return Response
     */
    public function dashboard(Request $request): Response
    {

        $user = $request->user();

        $select = [
            DB::raw('sum(amount) as total_amount'),
            DB::raw('sum(degree) as total_degree'),
        ];

        // 當天
        $today_data = Order::query()->select($select)
            ->where('user_id', $user['id'])
            ->where('ending_time', '>=', date('Y-m-d 00:00:00'))
            ->first();
        $today_total_amount = '0';
        $today_total_degree = '0.00';
        if ($today_data) {
            $today_total_amount = $today_data['total_amount'] ?? '0';
            $today_total_degree = $today_data['total_degree'] ?? '0.00';
        }

        // 當月
        $month_data = Order::query()->select($select)
            ->where('user_id', $user['id'])
            ->where('ending_time', '>=', date('Y-m-01 00:00:00'))
            ->first();
        $month_total_amount = '0';
        $month_total_degree = '0.00';
        if ($month_data) {
            $month_total_amount = $month_data['total_amount'] ?? '0';
            $month_total_degree = $month_data['total_degree'] ?? '0.00';
        }

        return $this->success([
            'month_total_amount' => $month_total_amount,
            'month_total_degree' => $month_total_degree,
            'today_total_amount' => $today_total_amount,
            'today_total_degree' => $today_total_degree,
        ]);

    }

    /**
     * 充電次數
     * @param Request $request
     * @return Response
     */
    public function chargeNumber(Request $request): Response
    {

        $user = $request->user();

        $select = [
            DB::raw('count(*) as today_count'),
        ];

        // 當天
        $today_data = Order::query()->select($select)
            ->where('user_id', $user['id'])
            ->where('ending_time', '>=', date('Y-m-d 00:00:00'))
            ->first();
        $today_count = 0;
        if ($today_data) {
            $today_count = $today_data['today_count'] ? intval($today_data['today_count']) : 0;
        }

        // 當月
        $month_data = Order::query()->select($select)
            ->where('user_id', $user['id'])
            ->where('ending_time', '>=', date('Y-m-01 00:00:00'))
            ->first();
        $month_count = 0;
        if ($month_data) {
            $month_count = $month_data['today_count'] ? intval($month_data['today_count']) : 0;
        }

        // 當年
        $year_data = Order::query()->select($select)
            ->where('user_id', $user['id'])
            ->where('ending_time', '>=', date('Y-01-01 00:00:00'))
            ->first();
        $year_count = 0;
        if ($year_data) {
            $year_count = $year_data['today_count'] ? intval($year_data['today_count']) : 0;
        }

        return $this->success([
            'today_count' => $today_count,
            'month_count' => $month_count,
            'year_count' => $year_count,
        ]);

    }

    /**
     * 統計數據
     *
     * @param HistoryRequest $request
     * @return Response
     */
    public function list(HistoryRequest $request): Response
    {

        $user = $request->user();

        // $year = $request->get('year', '');
        // $month = $request->get('month', '');
        $query_date = $request->get('query_date', '');

        $param = $request->only(['limit']);

        $select = [
            'id', 'order_number', 'pile_no', 'parking_lot_name', 'trade_date', 'starting_time', 'ending_time',
            'degree', 'duration', 'amount', 'card_number', 'invoice_info', 'status', 'star', 'total_parking_fee',
            'total_toll', 'preferential', 'total_charging',"brand_name"
        ];

        $year_list = Order::query()->select(DB::raw("DATE_FORMAT(created_at,'%Y') as y"))
            ->where('charging_status', 1)
            ->groupBy('y')
            ->orderBy('y')
            ->get()
            ->pluck('y');

        $model = Order::query()->select($select)->where('charging_status', 1);



        $begin = $end = '';
        if ($query_date) {
            $query_date_array = explode('-', $query_date);// var_dump($query_date_array);
            $query_date_count = count($query_date_array);

            if ($query_date_count > 2) {
                $begin = $query_date . ' 00:00:00';
                $end = $query_date . ' 23:59:59';
            } else {
                if ($query_date_count > 0) {
                    $year = intval($query_date_array[0]);
                    $begin = $year . '-01-01 00:00:00';
                    $end = ($year + 1) . '-01-01 00:00:00';
                }

                if ($query_date_count > 1) {
                    $year = intval($query_date_array[0]);
                    $month = intval($query_date_array[1]);
                    $query_month = $month;
                    if (strlen($query_month) == 1) {
                        $query_month = '0' . $month;
                    }

                    $begin = $year . '-' . $query_month . '-01 00:00:00';
                    if ($month < 12) {
                        $end_month = intval($query_month) + 1;
                        $end = $year . '-' . $end_month . '-01 00:00:00';
                    } else {
                        $end = ($year + 1) . '-01-01 00:00:00';
                    }
                }
            }

            if ($begin) {
                $model->where('ending_time', '>=', $begin);
            }

            if ($end) {
                $model->where('ending_time', '<', $end);
            }

        }

        // 當天
        $model->where('user_id', $user['id']);

        $list = $model->orderByDesc('id')->paginate($param['limit'] ?? 10);

        $data = $list->items();

        if ($data) {
            $order_ids = [];
            foreach($data as $k => $v) {
                $order_ids[] = $v['id'];
                $invoice_info = json_decode($v['invoice_info'], true);
                $data[$k]['invoice_info'] = $invoice_info['invoice_info']['title'] ?? '';
                $data[$k]['refund_or_not'] = 0;
                $data[$k]['refund_amount'] = 0;
                $data[$k]['refund_type'] = 0;
                $data[$k]['refund_time'] = '';
            }

            if ($order_ids) {
                $refund_list = OrderRefund::query()->select('order_id', 'refund_amount', 'type as refund_type', 'created_at as refund_time')
                    ->whereIn('order_id', $order_ids)
                    ->where('status', 2)->get()->toArray();
                $refund_list_map = [];
                if ($refund_list) {
                    foreach($refund_list as $v) {
                        $order_id = $v['order_id'];
                        unset($v['order_id']);
                        $refund_list_map[$order_id] = $v;
                    }

                    foreach($data as $k => $v) {
                        $order_id = $v['id'];
                        if (isset($refund_list_map[$order_id])) {
                            $data[$k]['refund_or_not'] = 1;
                            $data[$k]['refund_amount'] = $refund_list_map[$order_id]['refund_amount'];
                            $data[$k]['refund_type'] = $refund_list_map[$order_id]['refund_type'];
                            $data[$k]['refund_time'] = $refund_list_map[$order_id]['refund_time'];
                        }

                    }
                }
            }
        }

        return $this->success([
            'list' => $data,
            'total' => $list->total(),
            'years' => $year_list ?: date('Y')
        ]);

    }

    // 個人中心查看訂單，重新支付
    public function rePay(Request $request): Response
    {

        DB::beginTransaction();

        try {
            $user = $request->user();

            $order_id = $request->get('id', 0);
            $card_id = $request->get('card_id', 0);
            $invoice_id = $request->get('invoice_id', 0);
            $invoice_type = $request->get('invoice_type', 0);

            $status = 3;
            $order_info = Order::query()->where('id', $order_id)->where('user_id', $user['id'])->where('status', $status)->first();
            if (!$order_info) {
                throw new Exception('充電記錄不存在');
            }

            $need_update = [];
            if ($card_id > 0) {
                $card_info = CreditCard::query()->where('id', $card_id)->where('user_id', $user['id'])->first();
                if (!$card_info) {
                    // return $this->error('信用卡不正確');
                    throw new Exception('信用卡不正確');
                }
                $need_update['card_id'] = $card_id;
                $need_update['card_number'] = $card_info['card_number'];

                OrderCard::query()->where('order_id', $order_id)->delete();

                $order_card = [
                    'order_id' => $order_id,
                    'card_key' => $card_info['card_key'],
                    'card_token' => $card_info['card_token'],
                    'currency' => $card_info['currency'],
                ];
                OrderCard::query()->create($order_card);
            }

            if ($invoice_type > 0) {
                if ($invoice_type != 4) {
                    $invoice_info = Invoice::query()->select('title', 'tax_id')->where('id', $invoice_id)->where('user_id', $user['id'])->first();

                } else {
                    $invoice_info = InvoiceDonation::query()->select('institution as title', 'id_card as tax_id')->where('id', $invoice_id)->first();

                }
                if (!$invoice_info) {
                    // return $this->error('發票不正確');
                    throw new Exception('發票不正確');
                }

                $invoice_info_str = json_encode([
                    'invoice_info' => $invoice_info,
                ]);

                $need_update['invoice_type'] = $invoice_type;
                $need_update['invoice_id'] = $invoice_id;
                $need_update['invoice_info'] = $invoice_info_str;

            }

            // !empty($need_update) && Order::query()->where('id', $order_id)->update($need_update);
            if ($need_update) {
                Order::query()->where('id', $order_id)->where('user_id', $user['id'])->update($need_update);
                $order_info = Order::query()->where('id', $order_id)->where('user_id', $user['id'])->where('status', $status)->first();
            }

            if ((new PaymentService())->pay($order_info, $user['id'], $card_id)) {
                $status = 2;
                // $need_update['status'] = $status;

                // 開發票
                $res = (new InvoiceService())->sendOrder($order_info, $user);
                if ($res) {
                    !empty($res['invoice_number']) && Order::query()->where('id', $order_id)->update([
                        'invoice_number' => $res['invoice_number'],
                    ]);
                }

            }

            DB::commit();
            return $this->success(['status' => $status, 'id' => $order_id]);

        } catch (Throwable $e) {

            DB::rollBack();
            return $this->error($e->getMessage());
        }

    }


}
