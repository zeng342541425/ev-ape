<?php

namespace App\Services\Common;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class PileService
{

    //
    protected function requests(array $request_data = [], string $api = '')
    {

        $config = Config('pile');

        $http = new Client([
            'base_uri' => $config['domain'],
            'verify' => false,
        ]);

        Log::info("充電樁api {$api}請求資料：", ['data' => $request_data]);

        try {

            $response = $http->post($api, ['json' => $request_data]);
            $httpCode = $response->getStatusCode();
            $content = $response->getBody()->getContents();
            Log::info("充電樁api {$api}回傳資料：", ['http_code' => $httpCode, 'res' => $content ?: '']);

            if (200 == $httpCode) {

                if ($content) {
                    $res = json_decode($content, true);
                    if (isset($res['code']) && ($res['code'] == 200 || $res['code'] == 409)) {
                        // return intval($res['code']);
                        return 1;
                    }

                    return 0;
                }

            }

            return 0;

        } catch (GuzzleException $e) {
            Log::info("exception {$api}失敗111：", ['data' => $e->getMessage(), 'code' => $e->getCode()]);
        }
        return 0;
    }

    /**
     * @Description： 綁定充電樁機器碼
     * @param string $serial_number
     * @return bool
     * @author:
     */
    public function register(string $serial_number = ''): bool
    {
        Log::info('綁定充電樁， 編號為：', ['data' => $serial_number]);
        $config = Config('pile');
        if ($config['charging_debug']) {
            Log::info('register 不會請求充電樁API', ['data' => $serial_number]);
            return 1;
        }

        $signArray = [
            'nonce' => Common::nonceRandom(12),
            'token' => $config['token'],
            'serial_number' => $serial_number,
            'timestamp' => time(),
        ];

        $sign = SignService::generatedSign($signArray);

        $signArray['sign'] = $sign;
        $signArray['vendor'] = $config['codename'];
        unset($signArray['token']);

        return $this->requests($signArray, 'apis/client_app/register');

        // return $r == 200 || $r == 409;
    }

    /**
     * @Description： 開始充電
     * @param string $serial_number
     * @param int $order_id
     * @return bool
     * @author:
     */
    public function charging(string $serial_number = '', int $order_id = 0): bool
    {
        // // todo 臨時調試，正式需要打開
        // return 1;
        $config = Config('pile');
        if ($config['charging_debug']) {
            Log::info('charging 不會請求充電樁API', ['data' => $serial_number]);
            return true;
        }

        $signArray = [
            'nonce' => Common::nonceRandom(12),
            'token' => $config['token'],
            'serial_number' => $serial_number,
            'timestamp' => time(),
        ];

        $sign = SignService::generatedSign($signArray);

        $signArray['sign'] = $sign;
        $signArray['order_id'] = $order_id;
        $signArray['vendor'] = $config['codename'];
        unset($signArray['token']);

        return $this->requests($signArray, 'apis/client_app/start-charging');

        // return $f == 200;
    }

    public function pileStatus(string $serial_number = ''): bool
    {

        // return true;
        $config = Config('pile');
        if ($config['charging_debug']) {
            Log::info('pileStatus 不會請求充電樁API', ['data' => $serial_number]);
            return true;
        }

        $signArray = [
            'nonce' => Common::nonceRandom(12),
            'token' => $config['token'],
            'serial_number' => $serial_number,
            'timestamp' => time(),
        ];

        $sign = SignService::generatedSign($signArray);

        $signArray['sign'] = $sign;
        $signArray['vendor'] = $config['codename'];
        unset($signArray['token']);

        $config = Config('pile');

        $http = new Client([
            'base_uri' => $config['domain'],
            'verify' => false,
        ]);

        $api = 'apis/client_app/get-charger-status';
        Log::info("充電樁api {$api}請求資料：", ['data' => $signArray]);

        try {

            $response = $http->post($api, ['json' => $signArray]);
            $httpCode = $response->getStatusCode();
            $content = $response->getBody()->getContents();
            Log::info("充電樁api {$api}回傳資料：", ['http_code' => $httpCode, 'res' => $content ?: '']);

            if (200 == $httpCode) {

                if ($content) {
                    $res = json_decode($content, true);
                    if (isset($res['code']) && $res['code'] == 200 && isset($res['data']) && isset($res['data']['status'])) {
                       // return $res['data']['status'] == 1 || $res['data']['status'] == 0;
                        return $res['data']['status'] == 1 ;
                    }

                    return false;
                }

            }

            return false;

        } catch (GuzzleException $e) {
            Log::info("exception {$api}失敗：", ['data' => $e->getMessage(), 'code' => $e->getCode()]);
            return false;
        }

    }

}
