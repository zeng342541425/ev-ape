<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Requests\Frontend\Dining\BookRequest;
use App\Http\Requests\Frontend\Dining\RemainRequest;
use App\Http\Requests\Frontend\Dining\ScoreRequest;
use App\Jobs\InvoiceJob;
use App\Jobs\RegularPushJob;
use App\Jobs\EmailJob;
use App\Mail\Notice;
use App\Models\Common\DiningBooking;
use App\Models\Common\DiningBookingCard;
use App\Models\Common\DiningHotel;
use App\Models\Common\DiningHotelType;
use App\Models\Common\DiningSeat;
use App\Models\Common\InvoiceDonation;
use App\Models\Common\OrderCard;
use App\Models\User\CreditCard;
use App\Models\User\Invoice;
use App\Services\Common\Common;
use App\Services\Common\EmailCodeService;
use App\Services\Common\InvoiceService;
use App\Services\Common\PaymentService;
use App\Services\Common\TapPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\View;
use Throwable;


class DiningController extends BaseController
{

    /**
     * 可預約餐旅列表
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {

        $search_words = $request->get('search_words', '');
        $region_id = $request->get('region_id', '0');
        $village_id = $request->get('village_id', '0');
        $type_id = $request->get('type_id', []);

        $select = [
            'id', 'name', 'logo', 'address','type_id'
        ];

        $model = DiningHotel::query()->with("type_list:id,name")->select($select);

        if ($search_words) {
            $model->where(function ($q) use ($search_words) {
                $q->where('name', 'like', "%{$search_words}%");
                $q->orWhere('address', 'like', "%{$search_words}%");
            });
        }

        if (!empty($region_id)){
            $model->where('region_id',$region_id);
        }

        if (!empty($village_id)){
            $model->where('village_id',$village_id);
        }

        if (!empty($type_id)){
            $model->whereIn('type_id',$type_id);
        }

        $data = $model->select($select)
            ->where('status', 1)
            ->where('audit_status', 1)
            ->orderBy('sequencing')
            ->get()
            ->toArray();

        return $this->success(['list' => $data]);

    }

    /**
     * 餐廳類型
     * @return Response
     */
    public function typeList(): Response
    {
        $data = DiningHotelType::query()
            ->get(['id','name'])
            ->toArray();

        return $this->success(['list' => $data]);

    }

    // web訪問的
    public function partners(Request $request): Response
    {

        $search_words = $request->get('search_words', '');
        $region_id = $request->get('region_id', '0');
        $village_id = $request->get('village_id', '0');
        $type_id = $request->get('type_id', []);
        $select = [
            'id', 'name', 'logo', 'address', 'introduce','type_id'
        ];

        $model = DiningHotel::query()->with("type_list:id,name")->select($select);

        if ($search_words) {
            $model->where(function ($q) use ($search_words) {
                $q->where('name', 'like', "%{$search_words}%");
                $q->orWhere('address', 'like', "%{$search_words}%");
            });
        }

        if (!empty($region_id)){
            $model->where('region_id',$region_id);
        }

        if (!empty($village_id)){
            $model->where('village_id',$village_id);
        }

        if (!empty($type_id)){
            $model->whereIn('type_id',$type_id);
        }

        $data = $model->where('status', 1)
            ->where('audit_status', 1)
            ->orderBy('sequencing')
            ->get()
            ->toArray();

        if ($data) {
            foreach ($data as $k => $v) {
                $data[$k]['introduce'] = View::make('common.template', ['template_content' => $v['introduce'], 'template_title' => $v['name']])->render();

                $data[$k]['type_name'] = $v['type_list']['name']??"";

            }
        }

        return $this->success(['list' => $data]);

    }

    /**
     * 餐旅詳情
     *
     * @param Request $request
     * @return Response
     */
    public function detail(Request $request): Response
    {

        $id = $request->get('id', 0);
        $select = [
            'id', 'name', 'logo', 'address', 'introduce', 'things_to_know', 'notes', 'starting_time', 'ending_time', 'filter_days'
        ];
        $data = DiningHotel::query()->select($select)
            ->with('seat_info:id,dining_hotel_id,time,charging,id as seat_info_id,seats')
            ->where('id', $id)
            ->where('status', 1)
            ->where('audit_status', 1)->first();

        if (!empty($data['filter_days'])) {
            $data['filter_days'] = explode(',', $data['filter_days']);
        } else {
            $data['filter_days'] = [];
        }

        $webapp = $request->header('webapp');
        if (strtolower($webapp) == 'web') {
            $data['introduce'] = view('common.template',
                ['template_content' => $data['introduce'], 'template_title' => $data['name']])->render();
            $data['things_to_know'] = View::make('common.template',
                ['template_content' => $data['things_to_know'], 'template_title' => $data['name']])->render();
        } else {
            $data['introduce'] = view('common.app_template',
                ['template_content' => $data['introduce'], 'template_title' => $data['name']])->render();
            $data['things_to_know'] = View::make('common.app_template',
                ['template_content' => $data['things_to_know'], 'template_title' => $data['name']])->render();
        }

        return $this->success(['info' => $data]);

    }

    /**
     * 餐廳某時段可以預約的人數
     *
     * @param RemainRequest $request
     * @return Response
     */
    public function remainNumber(RemainRequest $request): Response
    {

        $id = $request->get('id', 0);
        $booking_date = $request->get('booking_date');
        $seat_info = DiningHotel::query()->where('id', $id)->first();
        if (!$seat_info) {
            return $this->error();
        }

        $seat_info = DiningSeat::query()->select('id', 'seats', 'time', 'charging')->where('dining_hotel_id', $id)->get()->toArray();
        if (!$seat_info) {
            return $this->error();
        }

        $seat_ids = [];
        $seat_map = [];
        foreach ($seat_info as $v) {
            $seat_info_id = $v['id'];
            unset($v['id']);
            $seat_ids[] = $seat_info_id;
            $seat_map[$seat_info_id] = $v;
            $seat_map[$seat_info_id]['seat_info_id'] = $seat_info_id;
            $seat_map[$seat_info_id]['remain_number'] = $v['seats'];
        }

        $exists = DiningBooking::query()->select(['seat_id', DB::raw('sum(number) as total_number')])
            ->whereIn('seat_id', $seat_ids)
            ->where('booking_date', $booking_date)
            ->whereIn('status',[0,1,2])
            ->groupBy('seat_id')
            ->get()->toArray();
        if ($exists) {
            foreach ($exists as $v) {
                $remain_number = intval($seat_map[$v['seat_id']]['seats'] - $v['total_number']);
                $seat_map[$v['seat_id']]['remain_number'] = max($remain_number, 0);
            }
        }

        return $this->success(['list' => array_values($seat_map)]);
    }

    /**
     * 提交預約
     *
     * @param BookRequest $request
     * @return Response
     */
    public function submit(BookRequest $request): Response
    {

        $user = $request->user();

        $seat_id = $request->get('seat_info_id');
        $data = $request->only(
            ['booking_date', 'birthday', 'number', 'gender', 'user_notes', 'card_id', 'invoice_id', 'invoice_type', 'booking_name']
        );

        if (empty($user['email'])) {
            return $this->error('請設定信箱');
        }

        $seat_info = DiningSeat::query()->where('id', $seat_id)->first();
        if (!$seat_info) {
            return $this->error();
        }

        $dining_hotel_id = $seat_info['dining_hotel_id'];
        $dining = DiningHotel::query()->where('id', $dining_hotel_id)->where('status', 1)
            ->where('audit_status', 1)
            ->first();
        if (!$dining) {
            return $this->error('預約失敗');
        }

        // 判斷預約日期是否對
        $booking_date = str_replace('-', '', substr($data['booking_date'], 0, 10));
        if ($booking_date < date('Ymd')) {
            return $this->error('預約失敗');
        }

        $starting_time = str_replace('-', '', substr($dining['starting_time'], 0, 10));
        $ending_time = str_replace('-', '', substr($dining['ending_time'], 0, 10));
        if ($booking_date < $starting_time || $booking_date > $ending_time) {
            return $this->error('預約失敗');
        }

        if (!empty($dining['filter_days'])) {
            if (in_array($data['booking_date'], explode(',', $dining['filter_days']))) {
                return $this->error('預約失敗，該日期不可預約');
            }
        }

        // 驗證人數
        $exists = DiningBooking::query()
            ->where('seat_id', $seat_id)
            ->where('booking_date', $data['booking_date'])
            ->whereIn('status',[0,1,2])
            ->first([DB::raw("sum(number) as total_number")]);
        if ($exists && $exists['total_number'] > 0 && $exists['total_number'] + $data['number'] > $seat_info['seats']) {
            $remain_number = $seat_info['seats'] - $exists['total_number'];
            return $this->error('預約失敗，剩餘人數為 ' . $remain_number);
        }

        $card_info = CreditCard::query()->where('id', $data['card_id'])->where('user_id', $user['id'])->first();
        if (!$card_info) {
            return $this->error('信用卡不正確');
        }

        $data['card_number'] = $card_info['card_number'];

        if ($data['invoice_type'] != 4) {
            $invoice_info = Invoice::query()->select('title', 'tax_id')
                ->where('id', $data['invoice_id'])
                ->where('user_id', $user['id'])->first();
        } else {
            $invoice_info = InvoiceDonation::query()->select('institution as title', 'id_card as tax_id')->where('id', $data['invoice_id'])->first();
            // $invoice_info = InvoiceDonation::query()->select('institution', 'code')->where('id', $data['invoice_id'])->first();

        }
        if (!$invoice_info) {
            return $this->error('發票不正確');
        }

        $invoice_info_str = json_encode([
            'invoice_info' => $invoice_info,
        ]);

        $cancel_days = $dining['cancel_days'];
        $hour = substr($seat_info['time'], 0, 2);
        $minute = substr($seat_info['time'], 3, 2);

        // 預約的時間小於當前時間
        $booking_datetime = $data['booking_date'] . ' ' . $seat_info['time'] . ':00';
        if (strtotime($booking_datetime) < time()) {
            return $this->error('預約失敗');
        }

        $data['name'] = $dining['name'];
        // $data['notes'] = $dining['notes'];
        $data['time'] = $seat_info['time'];
        $data['invoice_info'] = $invoice_info_str;
        $data['user_id'] = $user['id'];
        $data['phone'] = $user['phone'];
        $data['dining_hotel_id'] = $dining_hotel_id;
        $data['seat_id'] = $seat_id;
        $data['order_number'] = Common::generateNo();
        $data['charging'] = $seat_info['charging'];
        $data['cancel_days'] = $cancel_days;
        $data['booking_datetime'] = $booking_datetime;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        if ($cancel_days <= 0) {
            $cancel_days = 1;
        }
        $data['cancel_expired_at'] = date("Y-m-d 23:59:59", strtotime("-$cancel_days day", strtotime($booking_datetime)));
        // $interval = (int)date_diff(date_create(date('Y-m-d')), date_create(substr($data['booking_date'], 0, 10)))->format('%R%a');

        DB::beginTransaction();

        try {
            $rid = DiningBooking::query()->insertGetId($data);
            if (!$rid) {
                return $this->error();
            }

            $order_card = [
                'order_id' => $rid,
                'card_key' => $card_info['card_key'],
                'card_token' => $card_info['card_token'],
                'currency' => $card_info['currency'],
            ];
            DiningBookingCard::query()->create($order_card);

            DB::commit();

            // 推播
            $key = 'dining_booking_success';
            $replace = [
                'datetime' => $booking_datetime,
                'shop_name' => $dining['name'],
                'number' => $data['number'],
                'amount' => $data['charging'] * $data ['number'],
            ];
            RegularPushJob::dispatch($user['id'], $key, $replace);

            // 提醒用戶
            $email_data = [
                'username' => $user['name'],
                'shop_name' => $dining['name'],
                'order_no' => $data['order_number'],
                "datetime" => $booking_datetime,
                'number' => $data['number'],
                'amount' => $data['charging'] * $data ['number'],
            ];
           // EmailJob::dispatch($user['email'], $email_data, 2);
            // (new EmailCodeService($user['email'], $data, 2))->send();
            Mail::to($user['email'])->send(new Notice("dining_booking_success_user", $email_data));


            // 提醒商家
            $_data = [
                'username' => $user['name'],
                'phone' => $user['phone'],
                'shop_name' => $dining['name'],
                'order_no' => $data['order_number'],
                'month' => substr($data['booking_date'], 5, 2),
                'day' => substr($data['booking_date'], 8, 2),
                'time' => $seat_info['time'],
                'number' => $data['number'],
                'amount' => $data['charging'] * $data ['number'],
            ];
           // EmailJob::dispatch(env('BOOKING_EMAIL', 'evapeofficial@gmail.com'), $_data, 5);
            Mail::to(env('BOOKING_EMAIL', 'evapeofficial@gmail.com'))->send(new Notice("dining_booking_success_shop", $_data));

            return $this->success(['order_id' => $rid]);
        } catch (Throwable $e) {

            DB::rollBack();
            return $this->error($e->getMessage());
        }

    }

    /**
     * 已預約餐旅列表
     *
     * @param Request $request
     * @return Response
     */
    public function bookingList(Request $request): Response
    {

        $param = $request->only([
            'limit'
        ]);

        $user = $request->user();

        $select = ['id', 'dining_hotel_id', 'booking_date', 'number', 'time', 'status', 'booking_datetime','charging','payment_status'];
        $booking_list = DiningBooking::query()->select($select)->with('dining_hotel:id,name,address,logo')
            ->where('user_id', $user['id'])
            // ->orderByRaw('FIELD(`status`, 0, 1, 2, 3, 4)')
            ->orderBy('status')
            ->orderBy('booking_datetime')
            ->paginate($param['limit'] ?? 10);

        $data = $booking_list->items();

        foreach ($data as $k => $v) {
            $data[$k]['name'] = $v['dining_hotel']['name'];
            $data[$k]['address'] = $v['dining_hotel']['address'];
            $data[$k]['logo'] = $v['dining_hotel']['logo'];
            unset($data[$k]['dining_hotel']);

            // 已经过期
//            if ($v['status'] == 0 && (strtotime($v['booking_datetime']) < time() - 10 * 60)) {
//                $data[$k]['status'] = 2;
//            }

        }

        return $this->success([
            'list' => $data,
            'total' => $booking_list->total(),
        ]);
    }

    /**
     * 已預約詳情
     *
     * @param Request $request
     * @return Response
     */
    public function bookingDetail(Request $request): Response
    {

        $id = $request->get('id', 0);

        $user = $request->user();

        $select = ['id', 'dining_hotel_id', 'booking_date', 'number', 'time', 'user_notes', 'status',
            'booking_name', 'gender', 'birthday', 'charging', 'booking_datetime','payment_status'];
        $booking_detail = DiningBooking::query()->select($select)->with('dining_hotel:id,name,logo,address,notes')
            ->where('user_id', $user['id'])
            ->where('id', $id)
            ->first();

        $booking_detail['name'] = $booking_detail['dining_hotel']['name'];
        $booking_detail['logo'] = $booking_detail['dining_hotel']['logo'];
        $booking_detail['address'] = $booking_detail['dining_hotel']['address'];
        $booking_detail['notes'] = $booking_detail['dining_hotel']['notes'] ?? '';

        // 已经过期
//        if ($booking_detail['status'] == 0 && (strtotime($booking_detail['booking_datetime']) < time() - 10 * 60)) {
//            $booking_detail['status'] = 2;
//        }

        unset($booking_detail['dining_hotel']);

        return $this->success([
            'info' => $booking_detail,
        ]);

    }

    /**
     * 取消預約
     *
     * @param Request $request
     * @return Response
     */
    public function cancel(Request $request): Response
    {

        $id = $request->get('id', 0);

        $user = $request->user();

        $item = DiningBooking::query()
            ->where('user_id', $user['id'])
            ->where('id', $id)
            ->first();

        if (!$item) {
            return $this->error();
        }

        if ($item['status'] != 0) {
            return $this->error('已處理，取消預約失敗');
        }

        $key = '';
        $replace = [];
        $hotel = DiningHotel::query()->select('name')->where('id', $item['dining_hotel_id'])->first();
        if (strtotime($item['cancel_expired_at']) >= time()) {
            // 免費取消
            $r = DiningBooking::query()->where('id', $id)->update([
                'status' => 3,
                'payment_status' => 2,
            ]);
            if (!$r) {
                return $this->error('取消預約失敗');
            }


            $key = 'dining_booking_cancel_nopay';
            $replace = [
                'datetime' => $item['booking_datetime'],
                'shop_name' => $hotel['name'],
            ];

        } else {
            // 扣款
            $_replace = [
                'datetime' => $item['booking_datetime'],
                'shop_name' => $hotel['name'],
                'amount' => $item['number'] * $item['charging'],
                'order_no' => $item['order_number'],
            ];

            if ((new PaymentService())->payDining($id, $user['id'], 'payment_dining_has_not_left_notify')) {
                $r = DiningBooking::query()->where('id', $id)->update([
                    'status' => 3,
                    'payment_status' => 1,
                ]);

                $_key = 'dining_cancel_payment_success';

                $key = 'dining_booking_cancel';
                $replace = [
                    'datetime' => $item['booking_datetime'],
                    'shop_name' => $hotel['name'],
                ];

                // 開發票
                $invoiceService = new InvoiceService();
                $res = $invoiceService->send($item, $user, 2);
                if ($res) {
                    !empty($res['invoice_number']) && DiningBooking::query()->where('id', $id)->update([
                        'invoice_number' => $res['invoice_number'],
                    ]);
                }

            } else {
                $_key = 'dining_cancel_payment_fail';
            }

            RegularPushJob::dispatch($user['id'], $_key, $_replace);

        }

        // 推播
        !empty($key) && RegularPushJob::dispatch($user['id'], $key, $replace);

        // 提醒商家
        $_data = [
            'username' => $user['name'],
            'phone' => $user['phone'],
            'name' => $item['booking_name'],
            'shop_name' => $hotel['name'],
            'number' => $item['number'],
            'date' => $item['booking_date'],
            'time' => $item['time'],
            'order_no' => $item['order_number'],
            'amount' => $item['charging'] * $item['number'],
        ];
      //  EmailJob::dispatch(env('BOOKING_CANCEL_EMAIL', 'evapeofficial@gmail.com'), $_data, 6);

        Mail::to(env('BOOKING_CANCEL_EMAIL', 'evapeofficial@gmail.com'))->send(new Notice("dining_booking_cancel", $_data));

        return $this->success();

    }

    /**
     * 現場報到
     *
     * @param Request $request
     * @return Response
     */
    public function deal(Request $request): Response
    {

        $id = $request->get('id', 0);

        $user = $request->user();

        $item = DiningBooking::query()
            ->where('user_id', $user['id'])
            ->where('id', $id)
            ->first();

        if (!$item) {
            return $this->error();
        }

        if ($item['booking_date'] != date('Y-m-d')) {
            return $this->error('預約日期不對');
        }

        if ($item['status'] != 0) {
            return $this->error('已處理，現場報到失敗');
        }

        // 扣款
        $is_payment = 0;
        $pay_service = new PaymentService();
        if ($item['payment_status'] == 0) {
            if ($pay_service->payDining($id, $user['id'])) {
                $is_payment = 1;
            }
            // $request_data['filters']['order_number'] = $item['order_number'];
            // $res = $pay_service->query($request_data);
            // if (isset($res['trade_records']) && !empty($res['trade_records'])) {
            //     if (in_array($res['trade_records'][0]['record_status'], [0, 1])) {
            //         $is_payment = 1;
            //     }
            //     /**
            //     -1	ERROR	交易錯誤
            //     0	AUTH	銀行已授權交易，但尚未請款
            //     1	OK	交易完成
            //     2	PARTIALREFUNDED	部分退款
            //     3	REFUNDED	完全退款
            //     4	PENDING	待付款
            //     5	CANCEL	取消交易
            //      */
            // }
        } else {

            if ($item['payment_status'] == 1) {
                $is_payment = 1;
            }

        }

        $update_data = [
            'status' => 1,
            'payment_status' => 1,
            'reached_at' => date('Y-m-d H:i:s')
        ];

        $replace = [
            'amount' => $item['number'] * $item['charging'],
            'order_no' => $item['order_number'],
            'shop_name' => $item['name'],
            'booking_name' => $item['booking_name'],
            'username' => $user['name'],
        ];

        if ($is_payment) {
            // 修改狀態
            $r = DiningBooking::query()->where('id', $id)->update($update_data);
            if (!$r) {
                return $this->error('現場報到失敗');
            }

            //
            $invoiceService = new InvoiceService();
            $res = $invoiceService->send($item, $user, 2);
            if ($res) {
                !empty($res['invoice_number']) && DiningBooking::query()->where('id', $id)->update([
                    'invoice_number' => $res['invoice_number'],
                ]);
            }

            // 推播
            $key = 'dining_payment_success';

        } else {
            $update_data['payment_status'] = 3;

            // 修改狀態
            $r = DiningBooking::query()->where('id', $id)->update($update_data);
            if (!$r) {
                return $this->error('現場報到失敗');
            }

            // 推播
            $key = 'dining_payment_error';

            // return $this->error('扣款失敗');
        }
        RegularPushJob::dispatch($user['id'], $key, $replace);
        Mail::to($user['email'])->send(new Notice($key, $replace));

        return $this->success();

    }

    /**
     * 評分
     *
     * @param ScoreRequest $request
     * @return Response
     */
    public function score(ScoreRequest $request): Response
    {
        $id = $request->get('order_id');
        $star = $request->get('star');

        $user = $request->user();

        $item = DiningBooking::query()->select('id')->where('id', $id)->where('user_id', $user['id'])->first();
        if (!$item || $item['star'] != 0) {
            return $this->error();
        }

        DiningBooking::query()->where('id', $id)->update([
            'star' => $star
        ]);

        return $this->success();

    }

    public function rePay(Request $request)
    {
        $data = $request->only(['id', 'invoice_id', 'invoice_type', 'card_id']);
        $user = $request->user();

        $dining = DiningBooking::query()->where('user_id', $user['id'])->where('id', $data['id'])->first();

        if (!$dining) {
            return $this->error("記錄錯誤");
        }

        if ($dining->payment_status != 3) {
            return $this->error("狀態錯誤");
        }

        $card_info = CreditCard::query()->where('id', $data['card_id'])->where('user_id', $user['id'])->first();
        if (!$card_info) {
            return $this->error('信用卡不正確');
        }

        $data['card_number'] = $card_info['card_number'];

        if ($data['invoice_type'] != 4) {
            $invoice_info = Invoice::query()->select('title', 'tax_id')
                ->where('id', $data['invoice_id'])
                ->where('user_id', $user['id'])->first();
        } else {
            $invoice_info = InvoiceDonation::query()->select('institution as title', 'id_card as tax_id')->where('id', $data['invoice_id'])->first();
            // $invoice_info = InvoiceDonation::query()->select('institution', 'code')->where('id', $data['invoice_id'])->first();

        }
        if (!$invoice_info) {
            return $this->error('發票不正確');
        }

        $invoice_info_str = json_encode([
            'invoice_info' => $invoice_info,
        ]);
        $data['invoice_info'] = $invoice_info_str;

        $dining->invoice_info = $invoice_info_str;


        $replace = [
            'amount' => $dining['number'] * $dining['charging'],
            'order_no' => $dining['order_number'],
            'shop_name' => $dining['name'],
            'booking_name' => $dining['booking_name'],
            'username' => $user['name'],
        ];


        $key = 'dining_payment_success';

        DiningBookingCard::query()->where('order_id', $dining['id'])->delete();

        $order_card = [
            'order_id' => $dining['id'],
            'card_key' => $card_info['card_key'],
            'card_token' => $card_info['card_token'],
            'currency' => $card_info['currency'],
        ];
        DiningBookingCard::query()->create($order_card);

        $pay_service = new PaymentService();
        if (!$pay_service->payDining($dining->id, $user['id'])) {
            $key = 'dining_payment_error';

            RegularPushJob::dispatch($user['id'], $key, $replace);

            Mail::to($user['email'])->send(new Notice($key, $replace));
            return $this->error('扣款失敗');
        }

        $data['payment_status'] = 1;

        InvoiceJob::dispatch($data['id'],'send',2);

        $dining->update($data);
        RegularPushJob::dispatch($user['id'], $key, $replace);
        Mail::to($user['email'])->send(new Notice($key, $replace));
        return $this->success();


    }

}
