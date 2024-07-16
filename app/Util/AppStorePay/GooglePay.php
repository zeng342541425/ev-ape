<?php

namespace App\Util\AppStorePay;

use Google\Client;
use Google\Service\AndroidPublisher;
use Google\Service\AndroidPublisher\ProductPurchasesAcknowledgeRequest;
use Illuminate\Support\Facades\Log;

class GooglePay implements AppPayInterface
{
    public ?Client $client = null;

    /**
     * google pay 包名
     *
     * @var string
     */
    public string $googlePayPackageName;

    public function __construct()
    {
        $this->googlePayPackageName = config('app_store.google.package_name');
    }

    /**
     * @throws \Exception
     */
    public function checkPayCall(array $callData): array
    {
        try {
            $result = $this->getPurchaseResult($callData['productId'], $callData['purchaseToken']);

            $resultJson = (array)$result->toSimpleObject();

            Log::info("googlepay",$resultJson);


            if ($result->getPurchaseState() != 0) {
                throw new \Exception('支付狀態不為0');
            }

            if ($result->getAcknowledgementState() != 0) {
                throw new \Exception('訂單已確認');
            }


            return [
                'trade_no' => $result->getOrderId(),
                'payment_time' => date('Y-m-d H:i:s', intval($result->getPurchaseTimeMillis() / 1000)),
                'payment_callback_result' => $resultJson,
            ];


        } catch (\Throwable $e) {
            Log::error("googlepay錯誤".$e->getMessage());
            throw new \Exception("Google回調驗證失敗");
        }
    }

    /**
     * google授權
     *
     * @param $scope
     * @return void
     * @throws \Google\Exception
     */
    private function authorization($scope): void
    {
        if (!$this->client) {
            $this->client = new Client();
            $this->client->setAuthConfig(config('app_store.google.pay'));
        }
        //$client->setApplicationName('網路用戶端 1');
        //dd($client->getClientId(), $client->getClientSecret());

        $this->client->addScope($scope);
    }

    /**
     * 初始化AndroidPublisher
     *
     * @return AndroidPublisher
     */
    private function initAndroidPublisher(): AndroidPublisher
    {
        return new AndroidPublisher($this->client);
    }

    /**
     * 獲得google pay購買結果(一次性購買)
     *
     * @param $productId //google內購id
     * @param $purchaseToken //支付憑證
     * @return AndroidPublisher\ProductPurchase
     * @throws \Google\Exception
     */
    private function getPurchaseResult($productId, $purchaseToken): AndroidPublisher\ProductPurchase
    {
        $this->authorization(AndroidPublisher::ANDROIDPUBLISHER);

        //服務
        $service = $this->initAndroidPublisher();

        //購買訂閱==purchases_subscriptions

        //購買產品
        return $service->purchases_products->get($this->googlePayPackageName, $productId, $purchaseToken);
    }

    /**
     * 確認消費
     *
     * @param $productId //google內購id
     * @param $purchaseToken
     * @param $orderNo //訂單編號
     * @return mixed
     * @throws \Google\Exception
     */
    private function acknowledgePurchase($productId, $purchaseToken, $orderNo): mixed
    {
        $this->authorization(AndroidPublisher::ANDROIDPUBLISHER);

        //服務
        $service = $this->initAndroidPublisher();

        $acknowledgeRequest = new ProductPurchasesAcknowledgeRequest();

        $acknowledgeRequest->setDeveloperPayload($orderNo);

        //確認消耗
        return $service->purchases_products->acknowledge(
            $this->googlePayPackageName,
            $productId,
            $purchaseToken,
            $acknowledgeRequest
        );
    }



}
