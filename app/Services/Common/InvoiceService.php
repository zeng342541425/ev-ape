<?php

namespace App\Services\Common;

use App\Models\Common\DiningBooking;
use App\Models\Common\InvoiceDonation;
use App\Models\Order\InvoiceRequest;
use App\Models\Order\Order;
use App\Models\Order\OrderPaymentRequest;
use App\Models\User\CreditCard;
use App\Models\User\Invoice;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class InvoiceService
{

    public function send($data, $user, $type = 1)
    {

        $buyer_email = $user['email'] ?? '';
        $in_info = json_decode($data['invoice_info'], true);
        $invoice_info = $in_info['invoice_info'];

        $total_amount = $data['number'] * $data['charging'];
        $order_date = str_replace('-', '', substr($data['created_at'], 0, 10));

        $order_number = Common::generateInvoiceNo();

        $request_id = Str::uuid()->toString();



        $request_data = [
            'order_number' => $order_number,
            'order_date' => $order_date,
            'buyer_email' => $buyer_email,
            'currency' => 'TWD',
            'total_amount' => intval($total_amount),
            'details' => [
                [
                    'sequence_id' => '002',
                    'sub_amount' => intval($total_amount),
                    'unit_price' => intval($data['charging']),
                    'quantity' => intval($data['number']),
                    'description' => '【受託代銷】餐旅報到',
                    'tax_type' => 1,
                ]
            ],
            'notify_url' => route('invoice_notify')
        ];

        switch ($data['invoice_type']) {
            case 1: //手機條碼
                $request_data['carrier'] = ['type' => 1, 'number' => $invoice_info['title'] ?? ''];
                break;
            case 2: //自然人憑證
                $request_data['carrier'] = ['type' => 2, 'number' => $invoice_info['title'] ?? ''];
                break;
            case 3: //三聯發票

                $request_data['buyer_name'] = $invoice_info['title'] ?? '';
                $request_data['buyer_identifier'] = $invoice_info['tax_id'] ?? '';


                $tax_amount = round($total_amount / 1.05 * 0.05);
                $request_data['sales_amount'] = $total_amount - $tax_amount;
                $request_data['tax_amount'] = $tax_amount;

                break;
            case 4: //捐贈
                $request_data['npoban'] = $invoice_info['tax_id'] ?? '';
                break;
        }


        $in_request = InvoiceRequest::query()->where('type', 2)->where('order_id', $data['id'])->first();

        if ($in_request) {
            // $order_number = $in_request['order_number'];
            // $request_id = $in_request['request_id'];
            // $request_data = json_encode($in_request['requests_data']);
            InvoiceRequest::query()->where('type', 2)->where('order_id', $data['id'])->update([
                'order_number' => $order_number,
                'request_id' => $request_id,
                'requests_data' => json_encode($request_data),
            ]);

        } else {

            InvoiceRequest::query()->create([
                'user_id' => $user['id'],
                'order_id' => $data['id'],
                'order_number' => $order_number,
                'api' => 'einvoice/issue',
                'type' => 2,
                'request_id' => $request_id,
                // 'rec_trade_id' => $res['rec_invoice_id'] ?? '',
                // 'content' => json_encode($res),
                'requests_data' => json_encode($request_data),
            ]);

        }

        // $tapPayService = new TapPayService();
        $res = $this->invoiceRequests($request_data, $request_id);

        if ($res) {

            if (isset($res['status']) && $res['status'] == 0) {
                InvoiceRequest::query()->where('type', 2)->where('order_id', $data['id'])->update([
                    'rec_trade_id' => $res['rec_invoice_id'] ?? '',
                    'content' => json_encode($res),
                    'yeah' => 1
                ]);
                return $res;
            }
            InvoiceRequest::query()->where('type', 2)->where('order_id', $data['id'])->update([
                'rec_trade_id' => $res['rec_invoice_id'] ?? '',
                'content' => json_encode($res),
                'yeah' => 0
            ]);


        }

        return null;

    }

    // 充電開發票
    public function sendOrder($data, $user, $type = 1)
    {

        $buyer_email = $user['email'] ?? '';
        $in_info = json_decode($data['invoice_info'], true);
        $invoice_info = $in_info['invoice_info'];


        $total_amount = $data['amount'];
        $order_date = str_replace('-', '', substr($data['created_at'], 0, 10));
        $order_number = Common::generateInvoiceNo();
        $request_id = Str::uuid()->toString();

        $in_request = InvoiceRequest::query()->where('type', 1)->where('order_id', $data['id'])->first();





        $request_data = [
            'order_number' => $order_number,
            'order_date' => $order_date,
            'buyer_email' => $buyer_email,
            'currency' => 'TWD',
            'total_amount' => intval($total_amount),
            'details' => [
                [
                    'sequence_id' => '001',
                    'sub_amount' => intval($total_amount),
                    'unit_price' => intval($total_amount),
                    'quantity' => 1,
                    'description' => '充電',
                    'tax_type' => 1,
                ]
            ],
            'notify_url' => route('invoice_notify')
        ];

        switch ($data['invoice_type']) {
            case 1: //手機條碼
                $request_data['carrier'] = ['type' => 1, 'number' => $invoice_info['title'] ?? ''];
                break;
            case 2: //自然人憑證
                $request_data['carrier'] = ['type' => 2, 'number' => $invoice_info['title'] ?? ''];
                break;
            case 3: //三聯發票
                $request_data['buyer_name'] = $invoice_info['title'] ?? '';
                $request_data['buyer_identifier'] = $invoice_info['tax_id'] ?? '';

                $tax_amount = round($total_amount / 1.05 * 0.05);
                $request_data['sales_amount'] = $total_amount - $tax_amount;
                $request_data['tax_amount'] = $tax_amount;
                break;
            case 4: //捐贈
                $request_data['npoban'] = $invoice_info['tax_id'] ?? '';
                break;
        }

        if ($in_request) {
            // $request_data = json_decode($in_request['requests_data'], true);
            InvoiceRequest::query()->where('type', 1)->where('order_id', $data['id'])->update([
                'order_number' => $order_number,
                'request_id' => $request_id,
                'requests_data' => json_encode($request_data),
            ]);

        } else {

            InvoiceRequest::query()->create([
                'user_id' => $user['id'],
                'order_id' => $data['id'],
                'order_number' => $order_number,
                'api' => 'einvoice/issue',
                'type' => 1,
                'request_id' => $request_id,
                'requests_data' => json_encode($request_data),
            ]);

        }

        // $tapPayService = new TapPayService();
        $res = $this->invoiceRequests($request_data, $request_id);
        Log::info('invoiceRequests回傳數據：', ['data' => $res ?: []]);
        if ($res) {

            if (isset($res['status']) && $res['status'] == 0) {
                InvoiceRequest::query()->where('type', 1)->where('order_id', $data['id'])->update([
                    'rec_trade_id' => $res['rec_invoice_id'] ?? '',
                    'content' => json_encode($res),
                    'yeah' => 1
                ]);
                return $res;
            }
            InvoiceRequest::query()->where('type', 1)->where('order_id', $data['id'])->update([
                'rec_trade_id' => $res['rec_invoice_id'] ?? '',
                'content' => json_encode($res),
                'yeah' => 0
            ]);


        }

        return null;

    }

    /**
     * tappay發票請求方法
     * @param array $params
     * @param string $request_id
     * @param string $api
     * @return array
     */
    public static function invoiceRequests(array $params = [], string $request_id = '', string $api = 'einvoice/issue'): array
    {
        $config = Config('tappay');

        // $api = 'einvoice/issue';

        $http = new \GuzzleHttp\Client([
            'base_uri' => $config['invoice_domain'],
            'verify' => false,
            'headers' => [
                "x-api-key" => $config['invoice_api_key'],
                "Content-type" => 'application/json',
                "request-id" => $request_id,
            ]
        ]);

        $params['partner_key'] = $config['invoice_partner_key'];
        $params['seller_name'] = $config['seller_name'];
        $params['seller_identifier'] = $config['seller_identifier'];

        Log::info("{$api} post數據", ['data' => $params, 'request_id' => $request_id]);
        try {
            $response = $http->post($api, ['json' => $params]);
            $content = $response->getBody()->getContents();
            if (200 == $response->getStatusCode()) {

                Log::info("請求api {$api}成功：", ['res' => $content]);
                if ($content) {
                    $res = json_decode($content, true);
                    // if (isset($res['status']) && $res['status'] == 0) {
                    return $res;
                    // }

                }

                return [];

            } else {
                Log::info("請求api {$api}失敗：", ['res' => $content]);
            }

        } catch (GuzzleException $e) {
            Log::info("exception {$api}失敗：", ['data' => $params]);
        }

        return [];
    }

    /**
     * tappay 充電記錄發票折讓
     * @param array $params
     * @param string $order_number
     */
    public function allowanceOrder($params = [], $order_number = '')
    {

        $api = 'einvoice/allowance';
        $request_id = Str::uuid()->toString();
        $invoiceModel = InvoiceRequest::query()->where('order_number', $order_number)->where('type', 1)->first();

        $request_data = [
            'rec_invoice_id' => $invoiceModel['rec_trade_id'],
            'allowance_amount' => $params['amount'],
            'allowance_reason' => $params['allowance_reason'],
            'allowance_sale_amount' => $params['allowance_sale_amount'],
            'allowance_tax_amount' => $params['allowance_tax_amount'],
            'details' => [
                [
                    'sequence_id' => '001',
                    'sub_amount' => $params['allowance_sale_amount'],
                    'unit_price' => $params['allowance_sale_amount'],
                    'quantity' => 1,
                    'description' => '充電退費',
                    'tax_type' => 1,
                    'tax_amount' => 1,
                ]
            ],
        ];

        InvoiceRequest::query()->create([
            'user_id' => $invoiceModel['user_id'],
            'order_id' => $invoiceModel['order_id'],
            'order_number' => $order_number,
            'api' => $api,
            'type' => 1,
            'request_id' => $request_id,
            // 'rec_trade_id' => $res['rec_invoice_id'] ?? '',
            // 'content' => json_encode($res),
            'requests_data' => json_encode($request_data),
        ]);

        $res = $this->commonRequest($request_data, $request_id, $api);

        if ($res) {
            InvoiceRequest::query()->where('order_number', $order_number)->where('type', 1)->update([
                'rec_trade_id' => $res['rec_invoice_id'] ?? '',
                'content' => json_encode($res),
                'yeah' => 1
            ]);

            return true;

        }

        return false;
    }

    /**
     * tappay 作廢發票
     * @param array $params
     * @param string $request_id
     * @return array
     */
    public function invoiceVoid(array $params = [], string $request_id = ''): array
    {

        $api = 'einvoice/void';
        return $this->commonRequest($params, $request_id, $api);
    }

    protected function commonRequest(array $params = [], string $request_id = '', string $api = ''): array
    {
        $config = Config('tappay');

        $http = new \GuzzleHttp\Client([
            'base_uri' => $config['invoice_domain'],
            'verify' => false,
            'headers' => [
                "x-api-key" => $config['invoice_api_key'],
                "Content-type" => 'application/json',
                "request-id" => $request_id,
            ]
        ]);

        $params['partner_key'] = $config['invoice_partner_key'];

        Log::info("{$api} post數據", ['data' => $params, 'request_id' => $request_id]);
        try {
            $response = $http->post($api, ['json' => $params]);
            $content = $response->getBody()->getContents();
            if (200 == $response->getStatusCode()) {

                Log::info("請求api {$api}成功：", ['res' => $content]);
                if ($content) {
                    $res = json_decode($content, true);
                    if (isset($res['status']) && $res['status'] == 0) {
                        return $res;
                    }

                }

                return [];

            } else {
                Log::info("請求api {$api}失敗：", ['res' => $content]);
            }

        } catch (GuzzleException $e) {
            Log::info("exception {$api}失敗：", ['data' => $params]);
        }

        return [];
    }
}

