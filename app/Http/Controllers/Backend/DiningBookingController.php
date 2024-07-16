<?php

namespace App\Http\Controllers\Backend;

use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\DiningBooking\ListRequest;
use App\Http\Requests\Backend\DiningBooking\IdRequest;
use App\Http\Requests\Backend\DiningBooking\UpdateRequest;
use App\Jobs\EmailJob;
use App\Jobs\RegularPushJob;
use App\Mail\Notice;
use App\Models\Common\DiningBooking;
use App\Models\Common\DiningHotel;
use App\Models\Frontend\User\User;
use App\Services\Common\InvoiceService;
use App\Services\Common\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DiningBookingController extends Controller
{
    /**
     * 列表
     *
     * @param array $param
     * @param bool $paginate
     * @return array
     */
    protected function _list(array $param = [], bool $paginate = true): array
    {

        $query = DiningBooking::query();

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $search_words = $param['search_words'];
            $query->where(function ($q) use ($search_words) {
                $q->where('name', 'like', "%$search_words%");
                $q->orWhere('phone', 'like', "%$search_words%");
                $q->orWhere('booking_name', 'like', "%$search_words%");
                $q->orWhere('user_notes', 'like', "%$search_words%");
                $q->orWhere('notes', 'like', "%$search_words%");
                $q->orWhere('order_number', '=', "$search_words");
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

        if (!empty($param['booking_starting_time'])) {
            $starting_time = substr($param['booking_starting_time'], 0, 10);
            $query->where('booking_date', '>=', $starting_time);
        }

        if (!empty($param['booking_ending_time'])) {
            $ending_time = substr($param['booking_ending_time'], 0, 10);
            $query->where('booking_date', '<=', $ending_time);
        }

        if (isset($param['dining_hotel_id']) && is_numeric($param['dining_hotel_id']) && $param['dining_hotel_id'] > 0) {
            $query->where('dining_hotel_id', '=', $param['dining_hotel_id']);
        }

        if (isset($param['status']) && is_numeric($param['status'])) {
            $query->where('status', '=', $param['status']);
        }

        if (isset($param['payment_status']) && is_numeric($param['payment_status'])) {
            $query->where('payment_status', '=', $param['payment_status']);
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
     * 列表
     *
     * @param ListRequest $request
     * @return Response
     */
    public function list(ListRequest $request): Response
    {
        $param = $request->all();

        $data = $this->_list($param);

        return $this->success([
            'list' => $data['list'],
            'total' => $data['total']
        ]);
    }

    /**
     * 預約列表匯出
     *
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function export(Request $request): BinaryFileResponse
    {
        $headings = [
            '餐旅名稱', '會員帳號', '訂位大名', '性別', '生日', '訂單編號', '預約日期', '預約時段', '預約數量',
            '客戶備註', '訂單狀態', '付款狀態', '支付單號', '支付時間', '付款金額', '報到時間', '備註'
        ];

        $param = $request->all();

        $list = $this->_list($param, false);

        $data = [];
        if ($list) {
            $status_map = [
                0 => '待報到',
                1 => '已報到',
                2 => '未報到',
                3 => '已取消',
                4 => '已取消',
            ];
            $payment_status_map = [
                0 => '未扣款',
                1 => '已扣款',
                2 => '無需扣款',
                3 => '扣款失敗',
            ];
            foreach ($list as $v) {
                $data[] = [
                    $v['name'],
                    $v['phone'],
                    $v['booking_name'],
                    $v['gender'] == 1 ? '男' : '女',
                    $v['birthday'],
                    $v['order_number'],
                    $v['booking_date'],
                    $v['time'],
                    $v['number'],
                    $v['user_notes'],
                    $status_map[$v['status']] ?? '',
                    $payment_status_map[$v['payment_status']] ?? '',
                    $v['rec_trade_id'],
                    $v['payment_time'],
                    $v['number'] * $v['charging'],
                    $v['reached_at'],
                    $v['notes']
                ];
            }
        }

        return Excel::download(new BaseExport($data, $headings), '預約列表.xlsx');
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

        $item = DiningBooking::query()->find($id);

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
            'notes'
        ]);

        $item = DiningBooking::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        $param['updated_at'] = date('Y-m-d H:i:s');
        if (!$item->update($param)) {
            return $this->error(__('message.common.update.fail'));
        }

        return $this->success(null, __('message.common.update.success'));
    }

    /**
     * 取消
     *
     * @param IdRequest $request
     * @return Response
     */
    public function cancel(IdRequest $request): Response
    {
        $id = $request->post('id');

        $item = DiningBooking::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if ($item['status'] != 0) {
            return $this->error('訂單狀態為待報到才能操作');
        }

        $param['status'] = 4;
        $param['payment_status'] = 2;
        if (!$item->update($param)) {
            return $this->error();
        }

        $hotel = DiningHotel::query()->select('name')->where('id', $item['dining_hotel_id'])->first();
        $replace = [
            'datetime' => $item['booking_datetime'],
            'shop_name' => $hotel['name'],
        ];

        // 推播
        $key = 'dining_manage_cancel';
        RegularPushJob::dispatch($item['user_id'], $key, $replace);

        // 提醒用戶
        $user = User::query()->where('id', $item['user_id'])->first();

        $_data = [
            'username' => $user['name'],
            'shop_name' => $hotel['name'],
            'number' => $item['number'],
            'name' => $item['booking_name'],
            'date' => $item['booking_date'],
            'time' => $item['time'],
            'phone' => $user['phone'],
            'order_no' => $item['order_number'],
            'amount' => $item['charging'] * $item['number'],
        ];
        // EmailJob::dispatch(env('BOOKING_CANCEL_EMAIL', 'evapeofficial@gmail.com'), $_data, 9);
        // 提醒商家
        $email = env('BOOKING_CANCEL_EMAIL', 'evapeofficial@gmail.com');

        if (!empty($email)) {
            Mail::to($email)->send(new Notice("dining_manage_cancel_shop", $_data));
        }


        // 提醒用戶
        if (!empty($user['email'])) {
            Mail::to($user['email'])->send(new Notice("dining_manage_cancel_user", $_data));
        }


        return $this->success();
    }

    /**
     * 詳情
     *
     * @param IdRequest $request
     * @return Response
     */
    public function payment(IdRequest $request): Response
    {
        $id = $request->post('id');

        $item = DiningBooking::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        $pay_service = new PaymentService();

        if ($pay_service->payDining($item->id, $item->user_id)) {

            $r = DiningBooking::query()->where('id', $item->id)->update([
                'payment_status' => 1,
            ]);

            $key = 'dining_payment_supplement_success';

            // RegularPushJob::dispatch($v->user_id, $key, $replace);

            // 開發票
            $user = User::query()->where('id', $item->user_id)->first();
            $res = (new InvoiceService())->send($item, $user);
            if ($res) {
                !empty($res['invoice_number']) && DiningBooking::query()->where('id', $item->id)->update([
                    'invoice_number' => $res['invoice_number'],
                ]);
            }

            $replace = [
                'datetime' => $item->booking_datetime,
                'amount' => $item->number * $item->charging,
                'order_no' => $item->order_number
            ];
            RegularPushJob::dispatch($item->user_id, $key, $replace);

            return $this->success();

        }
        // $r = DiningBooking::query()->where('id', $v->id)->update([
        //     'payment_status' => 3,
        // ]);

        $key = 'dining_payment_supplement';
        $replace = [
            'datetime' => $item->booking_datetime,
            'amount' => $item->number * $item->charging,
            'order_no' => $item->order_number
        ];
        RegularPushJob::dispatch($item->user_id, $key, $replace);
        return $this->error('扣款失敗');


    }

}
