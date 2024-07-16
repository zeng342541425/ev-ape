<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\User\Card\BindRequest;
use App\Http\Requests\Frontend\User\Card\UnbindRequest;
use App\Jobs\RegularPushJob;
use App\Models\Order\Order;
use App\Models\User\CardRequests;
use App\Models\User\CreditCard;
use App\Services\Common\TapPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;


class CardController extends BaseController
{

    /**
     * 卡列表
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {

        $user = $request->user();

        $card_list = $this->_list($user['id']);
        return $this->success(['list' => $card_list]);

    }

    /**
     * 綁定卡
     * @param BindRequest $request
     * @return Response
     */
    public function bind(BindRequest $request): Response
    {

        // $prime = $request->get('prime');
        // $amount = $request->get('amount');
        // $cardholder = $request->get('cardholder');

        $user = $request->user();
        $card_exists_number = CreditCard::query()->where('user_id', $user['id'])->count();
        $card_max = config('evape.card_max');
        if ($card_exists_number >= $card_max) {
            return $this->error("信用卡最多{$card_max}張");
        }

        $currency = $request->get('currency', 'TWD');
        $request_data = [
            'prime' => $request->get('prime'),
            // 'cardholder' => $request->get('cardholder', ''),
            'currency' => $currency,
        ];

        // 持卡人或購買人資訊
        $request_data['cardholder'] = [
            'phone_number' => $user['phone'],
            'name' => $user['name'] ?? '',
            'email' => $user['email'] ?? '',
            'zip_code' => '',
            'address' => '',
            'national_id' => '',
            'member_id' => '',
            'bank_member_id' => '',
        ];

        $tapPayService = new TapPayService();
        $res = $tapPayService->bind($request_data);

        if ($res) {

            if (!in_array($res['card_info']['funding'],[0,1])) {
                return $this->error('該卡片暫不支持');
            }

            if (isset($res['card_identifier']) && !empty($res['card_identifier'])
                && CreditCard::query()->where('user_id', $user['id'])->where('card_identifier', $res['card_identifier'])->exists()) {
                return $this->error('不可重複綁定');
            }

//            $current_date = date('Y-m-d H:i:s');
//            $card_number = $res['card_info']['bin_code'] . str_repeat('*', 6) . $res['card_info']['last_four'];
//            $card_id = CreditCard::query()->insertGetId([
//                'user_id' => $user['id'],
//                'card_number' => $card_number,
//                'card_key' => $res['card_secret']['card_key'],
//                'card_token' => $res['card_secret']['card_token'],
//                'currency' => $currency,
//                'funding' => $res['card_info']['funding'],
//                'type' => $res['card_info']['type'],
//                'card_identifier' => $res['card_identifier'],
//                'created_at' => $current_date,
//                'updated_at' => $current_date,
//            ]);
//
//            if (!$card_id) {
//                return $this->error();
//            }

            CardRequests::query()->create([
                'user_id' => $user['id'],
                'card_id' => 0,
                'api' => $tapPayService->getCardBindApi(),
                'rec_trade_id' => $res['rec_trade_id'] ?? '',
                'order_id' => $res['order_id'] ?? '',
                'auth_code' => $res['auth_code'] ?? '',
                'content' => json_encode($res),
                'requests_data' => json_encode($request_data),
            ]);

           // $card_list = $this->_list($user['id']);

            if (empty($res['payment_url'])){
                return $this->error('開啟3D驗證失敗');
            }

//            // 推播
//            $key = 'card_bind_success';
//            RegularPushJob::dispatch($user['id'], $key);

            return $this->success(['payment_url' => $res['payment_url']]);

        }

        return $this->error('授權失敗');

    }

    protected function _list(int $user_id = 0): array
    {
        $select = ['id', 'card_number', 'default', 'type'];
        return CreditCard::query()->select($select)
            ->where('user_id', $user_id)
            ->orderBy('default', 'desc')->get()->toArray();
    }


    /**
     * 刪除卡
     * @param UnbindRequest $request
     * @return Response
     */
    public function unbind(UnbindRequest $request): Response
    {

        $user = $request->user();

        $card_id = $request->get('id');
        $card_info = CreditCard::query()->where('id', $card_id)->where('user_id', $user['id'])->first();
        if (!$card_info) {
            return $this->error('信用卡不存在');
        }

        $request_data = [
            'card_key' => $card_info['card_key'],
            'card_token' => $card_info['card_token'],
        ];

        // 如果用戶
        $e = Order::query()
            ->where('user_id', $user['id'])
            ->where('card_id', $card_id)
            ->where('charging_status', 0)
            ->first();
        if ($e) {
            return $this->error('操作失敗，有正在充電記錄選擇了該信用卡');
        }

        $tapPayService = new TapPayService();
        $res = $tapPayService->unbind($request_data);

        if (isset($res['status']) && $res['status'] == 0) {

            CardRequests::query()->create([
                'user_id' => $user['id'],
                'card_id' => $card_id,
                'api' => $tapPayService->getCardRemoveApi(),
                'rec_trade_id' => $res['rec_trade_id'] ?? '',
                'order_id' => $res['order_id'] ?? '',
                'auth_code' => $res['auth_code'] ?? '',
                'content' => json_encode($res),
                'requests_data' => json_encode($request_data),
            ]);

            if (CreditCard::query()->where('id', $card_id)->where('user_id', $user['id'])->delete()) {
                return $this->success();
            }

        }

        return $this->error();

    }

    /**
     * 默認支付卡
     * @param Request $request
     * @return Response
     */
    public function setDefault(Request $request): Response
    {

        $user = $request->user();

        $card_id = $request->get('id');
        $card_info = CreditCard::query()->where('id', $card_id)->where('user_id', $user['id'])->first();
        if (!$card_info) {
            return $this->error('信用卡不存在');
        }

        DB::beginTransaction();

        try {
            CreditCard::query()->where('id', $card_id)->where('user_id', $user['id'])->update([
                'default' => 1
            ]);

            CreditCard::query()->whereNot('id', $card_id)->where('user_id', $user['id'])->update([
                'default' => 0
            ]);

            DB::commit();

            return $this->success();

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }

    }

}
