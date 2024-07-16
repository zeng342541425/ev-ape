<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\User\Invoice\BindRequest;
use App\Http\Requests\Frontend\User\Invoice\UnbindRequest;
use App\Models\Common\InvoiceDonation;
use App\Models\Order\Order;
use App\Models\User\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;


class InvoiceController extends BaseController
{

    /**
     * 發票列表
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
     * 發票列表
     * @return Response
     */
    public function donations(): Response
    {


        $list = InvoiceDonation::query()->select('id','id_card','code', 'institution')->where('status', 1)->get()->toArray();
        $res = [];
        if ($list) {
            foreach($list as $vv) {
                $res[] = [
                    'id' => $vv['id'],
                    'tax_id' => $vv['id_card'],
                    'title' => $vv['institution'],
                    "type" => 4,
                    "default" => 0
                ];
            }
        }
        return $this->success(['list' => $res]);

    }

    /**
     * 新增發票
     * @param BindRequest $request
     * @return Response
     */
    public function bind(BindRequest $request): Response
    {

        $user = $request->user();
        // $_max = config('evape.invoice_max');
        // if ($_exists_number >= $_max) {
        //     return $this->error("信用卡最多{$_max}張");
        // }

        $title = $request->get('title');
        $type = $request->get('type');

        $create_data = [
            'title' => $title,
            'type' => $type,
            'user_id' => $user['id'],
        ];
        if ($type == 3) {
            $tax_id = $request->get('tax_id');

            if (empty($tax_id)) {
                return $this->error('統一編號須填寫');
            }

            // 三聯式發票：總長度共8碼，全部為數字型態。
            if (strlen($tax_id) != 8 || !preg_match('/^[0-9]{8}$/', $tax_id)) {
                return $this->error('格式不正確');
            }

            $create_data['tax_id'] = $tax_id;
        }

        // 驗證發票 1:手機條碼;2:自然人憑證;3:三聯發票
        if ($type == 1) {
            // 手機條碼：總長度為8碼字元，第一碼必為「/」。
            if (!preg_match('/^\/[0-9A-Z\+\-\.]{7}$/', $title)) {
                return $this->error('格式不正確');
            }
        } else if($type == 2){
            // 自然人憑證：碼數兩英文加上14碼數字。
            if (!preg_match('/^[A-Z]{2}[0-9]{14}$/', $title)) {
                return $this->error('格式不正確');
            }
        }

        // 需要驗證是否正在充電，如果正在充電，不能刪除
        $_exists = Invoice::query()->where('user_id', $user['id'])->where('type', $type)->first();
        if ($_exists) {
            $e = Order::query()
                ->where('user_id', $user['id'])
                ->where('invoice_type', $type)
                ->where('invoice_id', $_exists['id'])
                ->where('charging_status', 0)
                ->first();
            if ($e) {
                return $this->error('操作失敗，有正在充電記錄選擇了該發票');
            }
            $r = Invoice::query()->where('user_id', $user['id'])->where('type', $type)->update($create_data);
        } else {

            $r = Invoice::query()->create($create_data);

        }

        $_list = $this->_list($user['id']);
        return $this->success(['list' => $_list]);

    }

    protected function _list(int $user_id = 0): array
    {
        $select = ['id', 'title', 'tax_id', 'type', 'default'];
        return Invoice::query()->select($select)
            ->where('user_id', $user_id)
            ->orderBy('default', 'desc')->get()->toArray();
    }


    /**
     * 刪除發票
     * @param UnbindRequest $request
     * @return Response
     */
    public function remove(UnbindRequest $request): Response
    {

        $user = $request->user();

        $invoice_id = $request->get('id');
        $invoice_info = Invoice::query()->where('id', $invoice_id)->where('user_id', $user['id'])->first();
        if (!$invoice_info) {
            return $this->error('發票不存在');
        }

        $e = Order::query()
            ->where('user_id', $user['id'])
            ->where('invoice_type', $invoice_info['type'])
            ->where('invoice_id', $invoice_id)
            ->where('charging_status', 0)
            ->first();
        if ($e) {
            return $this->error('操作失敗，有正在充電記錄選擇了該發票');
        }

        if (Invoice::query()->where('id', $invoice_id)->where('user_id', $user['id'])->delete()) {
            return $this->success();
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

        $id = $request->get('id');
        $card_info = Invoice::query()->where('id', $id)->where('user_id', $user['id'])->first();
        if (!$card_info) {
            return $this->error('發票信息不存在');
        }

        DB::beginTransaction();

        try {
            Invoice::query()->where('id', $id)->where('user_id', $user['id'])->update([
                'default' => 1
            ]);

            Invoice::query()->whereNot('id', $id)->where('user_id', $user['id'])->update([
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
