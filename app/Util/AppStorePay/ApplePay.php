<?php

namespace App\Util\AppStorePay;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ApplePay implements AppPayInterface
{

    /**
     * apple密鑰
     *
     * @var string
     */
    public string $appleSecret = '';

    /**
     * apple沙盒環境 true 是 false 否
     *
     * @var bool
     */
    public bool $appleSandbox = false;

    /**
     * apple bundle標識
     *
     * @var string
     */
    public string $appleBundleId = '';

    public function __construct()
    {
        $this->appleSecret = config('app_store.apple.secret');
        $this->appleBundleId = config('app_store.apple.bundle_id');
        $this->appleSandbox = config('app_store.apple.sandbox');
    }

    /**
     * @throws \Exception
     */
    public function checkPayCall(array $callData): array
    {
        try {

            $res = $this->sendReceiptDataToApple($callData['receiptData'], $this->appleSandbox);

            if ($res['status'] != 0) {
                throw new \Exception('狀態不為0');
            }

            $inAppData = $res['receipt']['in_app'][0];


            if (
                $res['receipt']['bundle_id'] != $this->appleBundleId
            ) {
                throw new \Exception('apple bundle標識不一致');
            }

            return [
                    'trade_no' => $inAppData['transaction_id'],
                    'payment_time' => date('Y-m-d H:i:s', strtotime($inAppData['purchase_date_pst'])),
                    'payment_callback_result' => $res
                ];

        } catch (\Throwable $e) {
            Log::error("applepay錯誤" . $e->getMessage());
            throw new \Exception("APPLE回調驗證失敗");
        }
    }

    /**
     * 發送憑證數據到apple
     *
     * @param $receiptData
     * @param $sandbox
     * @return mixed
     * @throws \Throwable
     */
    protected function sendReceiptDataToApple($receiptData, $sandbox = false): mixed
    {
        $client = new Client([
            'base_uri' => $sandbox
                ? 'https://sandbox.itunes.apple.com/verifyReceipt'
                : 'https://buy.itunes.apple.com/verifyReceipt',
            'timeout' => 10.0
        ]);

        $response = $client->request('POST', '', [
            'json' => [
                'receipt-data' => $receiptData,
                'password' => $this->appleSecret,
            ]
        ]);

        $body = $response->getBody();

        $body = json_decode($body, true);

        Log::info("applepay", $body);

        return $body;
    }

}
