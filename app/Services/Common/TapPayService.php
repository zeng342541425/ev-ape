<?php

namespace App\Services\Common;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TapPayService
{

    // tappay partner_key
    private string $partner_key = '';

    private string $bind_merchant_id = '';
    private string $pay_merchant_id = '';
    private string $card_bind_api = '';
    private string $card_remove_api = '';
    private string $trade_history_api = '';
    private string $payment_by_token_api = '';
    private string $refund_api = '';
    private string $query_api = '';
    private string $domain = '';
    private array $config = [];

    public function __construct()
    {
        $config = config('tappay');
        $this->config = $config;
        $this->partner_key = $config['partner_key'];
        $this->bind_merchant_id = $config['bind_merchant_id'];
        $this->pay_merchant_id = $config['pay_merchant_id'];
        $this->domain = $config['domain'];
        $this->card_bind_api = $config['card_bind_api'];
        $this->card_remove_api = $config['card_remove_api'];
        $this->trade_history_api = $config['trade_history_api'];
        $this->payment_by_token_api = $config['payment_by_token_api'];
        $this->refund_api = $config['refund_api'];
        $this->query_api = $config['query_api'];
    }

    public function getPartnerKey()
    {
        return $this->partner_key;
    }



    public function getCardBindApi()
    {
        return $this->card_bind_api;
    }

    public function getCardRemoveApi()
    {
        return $this->card_remove_api;
    }

    public function getPaymentByTokenApi()
    {
        return $this->payment_by_token_api;
    }

    /**
     * tappay請求方法
     * @param array $params
     * @param string $api
     * @return array
     */
    protected function requests(array $params = [], string $api = ''): array
    {
        $http = new \GuzzleHttp\Client([
            'base_uri' => $this->domain,
            'verify' => false,
            'headers' => [
                "x-api-key" => $this->partner_key,
                "Content-type" => 'application/json'
            ]
        ]);

        $url = $this->domain."/".$api;

        Log::info("{$url} post數據", ['data' => $params]);
        try {
            $response = $http->post($api, ['json' => $params]);
            $content = $response->getBody()->getContents();
            if (200 == $response->getStatusCode()) {

                Log::info("請求api {$url}成功：", ['res' => $content]);
                if ($content) {
                    $res = json_decode($content, true);
                    if (isset($res['status']) && $res['status'] == 0) {
                        return $res;
                    }

                }

                return [];

            } else {
                Log::info("請求api {$url}失敗：", ['res' => $content]);
            }

        } catch (GuzzleException $e) {
            Log::info("exception {$url}失敗：", ['data' => $params]);
        }

        return [];
    }

    /**
     * 綁卡
     * @param array $params
     * @return array
     */
    public function bind(array $params = []): array
    {

        $params['partner_key'] = $this->partner_key;
        $params['merchant_id'] = $this->bind_merchant_id;
        $params['three_domain_secure'] = true;
        $params[ 'result_url'] = [
        'backend_notify_url' => route('backend_bind_notify_url'),
        'frontend_redirect_url' => route('frontend_bind_redirect_url'),
    ];
        return $this->requests($params, $this->card_bind_api);

    }

    /**
     * 解綁卡
     * @param array $params
     * @return array
     */
    public function unbind(array $params = []): array
    {
        $params['partner_key'] = $this->partner_key;
        return $this->requests($params, $this->card_remove_api);

    }

    /**
     * 單筆交易的詳細狀態
     * @param array $params
     * @return array
     */
    public function history(array $params = []): array
    {
        /**
        {
        "partner_key": String,
        "rec_trade_id": String
        }
         */
        $params['partner_key'] = $this->partner_key;
        return $this->requests($params, $this->trade_history_api);

    }

    /**
     * 交易
     * @param array $params
     * @return array
     */
    public function pay(array $params = []): array
    {
        /**
        {
        "card_key": String,
        "card_token": String,
        "partner_key": String,
        "currency": "TWD",
        "merchant_id": "merchantA",
        "details":"TapPay Test",
        "amount": 100
        }
         */
        $params['partner_key'] = $this->partner_key;
        $params['merchant_id'] = $this->pay_merchant_id;
        return $this->requests($params, $this->payment_by_token_api);

    }

    /**
     * 退款
     * @param array $params
     * @return array|mixed
     */
    public function refund(array $params = []): mixed
    {
        /**
        {
        "partner_key": String,
        "rec_trade_id": String,
        "bank_refund_id": "fauouoiuo682783" 商戶定義的退款紀錄識別碼(需為半形的英數字)，不可重複。
        "amount": int // 非必填 退款金額，全額退款可不用填此參數
        外幣金額需包含兩位小數，如 100 代表 1.00
        部分退款才需要填寫
        }
         */
        $params['partner_key'] = $this->partner_key;
        return $this->requests($params, $this->refund_api);

    }

    /**
     * 單筆交易的詳細狀態
     * @param array $params
     * @return array
     */
    public function query(array $params = []): array
    {
        /**
        {
        "partner_key": String,
        "rec_trade_id": String
        }
         */
        $params['partner_key'] = $this->partner_key;
        return $this->requests($params, $this->query_api);

    }

    /**
     * tappay發票請求方法
     * @param array $params
     * @param string $request_id
     * @return array
     */
    public function invoiceRequests(array $params = [], string $request_id=''): array
    {

        $api = 'einvoice/issue';

        $http = new \GuzzleHttp\Client([
            'base_uri' => $this->config['invoice_domain'],
            'verify' => false,
            'headers' => [
                "x-api-key" => $this->config['invoice_api_key'],
                "Content-type" => 'application/json',
                "request-id" => $request_id,
            ]
        ]);

        $params['partner_key'] = $this->config['invoice_partner_key'];
        $params['seller_name'] = $this->config['seller_name'];
        $params['seller_identifier'] = $this->config['seller_identifier'];

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

