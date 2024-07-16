<?php

namespace App\Services\Common;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Common
{

    /**
     * @Description：生成隨機數
     * @param int $length
     * @param int $type
     * @return string
     */
    public static function generatedNonce(int $length = 6, int $type = 1): string
    {

        return env('PHONE_CODE_DEBUG') ? '1234' : self::nonceRandom($length, $type);

    }

    public static function nonceRandom(int $length = 6, int $type = 1): string
    {
        $string = '';

        $s = '1234567890';
        if ($type == 1) {
            $s = '1234567890abcdefghijklmnopqrstuvwxyz';
        }

        $s_length = strlen($s);
        while($length) {
            try {
                $r = random_int(1, $s_length);
            } catch (Exception $e) {
            }
            $string .= substr($s, $r-1, 1);
            $length--;
        }

        return $string;

    }

    /**
     * 叫貨編號
     *
     * @return string
     */
    public static function generateNo(): string
    {

        return substr(date('YmdHis'), 2) . substr(explode(' ', microtime())[0], 2, 5) . mt_rand(0, 9);

    }

    /**
     * 支付編號
     *
     * @return string
     */
    public static function generatePaymentNumber(): string
    {

        return substr(date('YmdHis'), 2) . substr(explode(' ', microtime())[0], 2, 5) . mt_rand(0, 9);

    }

    /**
     * 計算充電金額
     *
     * @param int $duration
     * @param int $charging
     * @return int
     */
    public static function getAmount(int $duration = 0, int $charging = 0): int
    {

        // 充電收費方式為插槍拔槍計算，不足 30 分鐘以 30 分鐘計算
        // 後台設定 60 元/一小時，充電 1-30 分鐘收 30 元、充電 30-60 分鐘收 60 元、充電 60-90 分鐘收 90 元，以此類推。
        $per_30minute = intval($charging / 2);
        $p = intval($duration / 30);
        $r = ($duration - $p * 30) > 0 ? 1 : 0;
        return  $r * $per_30minute + $p * 30;

    }

    public static function generateInvoiceNo(): string
    {

        return date('ymdHis').mt_rand(1000, 9999).mt_rand(1000, 9999);

    }


}

