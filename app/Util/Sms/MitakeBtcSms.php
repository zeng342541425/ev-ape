<?php

namespace App\Util\Sms;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class MitakeBtcSms
{
    protected string $baseUri = 'https://smsb2c.mitake.com.tw/b2c/mtk/';

    protected Client $client;

    /**
     * 配置
     */
    protected array $config = [
        'username' => '',
        'password' => '',
    ];

    /**
     * @param $config
     */
    public function __construct($config = null)
    {
        $this->config = $config ?? config('sms.mitake_sms');

        $this->client = new Client([
            'base_uri' => $this->baseUri,
        ]);

    }

    public static function make(?array $config = null): static
    {
        return new static($config);
    }

    /**
     * 單筆簡訊發送 SmSend
     *
     * @param string $phone 必要 收訊人之手機號碼
     * 格式為：0912345678。
     *
     * @param string $content 必要 簡訊內容
     * 若有 換行 的需求，請填入 ASCII Code 6 代表換行。
     * 為避免訊息中有 & 分隔符號，請將此欄位進行 URL Encode (Big5或UTF8)。
     * 若使用者帳號沒有長簡訊發送權限，當發送內容為長簡訊時，
     * 簡訊內容會被截斷為短簡訊後發送。
     *
     * @param string|null $destName 收訊人名稱。
     * 若其他系統需要與簡訊資料進行系統整合，
     * 此欄位可填入來源系統所產生的Key值，以對應回來源系統。
     * 若帶入中文字串，請將此欄位進行 URL Encode (Big5或UTF8)
     *
     * @param string|int|null $dlvTime 簡訊預約時間
     * 格式為 YYYYMMDDHHMMSS 或整數值代表幾秒後傳送。
     * 即時發送：輸入的預約時間小等於系統時間或輸入空白。
     * 預約發送：輸入的預約時間大於系統時間10分鐘
     *
     * @param string|int|null $vldTime 簡訊有效期限
     * 格式為 YYYYMMDDHHMMSS 或整數值代表傳送後幾秒後內有效。
     * 若未指定則預設為24小時。
     * 請勿超過電信業者預設之24小時期限，以避免業者不回覆簡訊狀態。
     *
     * @return array = [
     *      1 => [
     *          "msgid" => "0824998098",
     *          "statuscode" => "1",
     *          "AccountPoint" => "9339"
     *      ]
     * ]
     * @throws GuzzleException
     */
    public function smSend(
        string $phone,
        string $content,
        ?string $destName = null,
        string|int|null $dlvTime = null,
        string|int|null $vldTime = null
    ): array
    {
        $param = [
            'username' => $this->config['username'],
            'password' => $this->config['password'],
            'dstaddr' => $phone,
            'smbody' => $this->toBig5($content),
        ];
        if (isset($destName)) $param['destname'] = $destName;
        if (isset($dlvTime)) $param['dlvtime'] = $dlvTime;
        if (isset($vldTime)) $param['vldtime'] = $vldTime;

        $uri = 'SmSend';
        $response = $this->client->post($uri, [
            'query' => ['CharsetURL' => 'Big5',],
            'form_params' => $param,
        ]);
        $data = $this->parseResponse($response->getBody()->getContents());

        Log::debug('MitakeBtcSms->Request', [
            'uri' => $this->baseUri . $uri,
            'body' => $param,
            'response' => $data,
        ]);

        return $data;
    }

    /**
     * 轉為 Big5 編碼
     *
     * @param $body
     *
     * @return array|false|string|string[]|null
     */
    protected function toBig5($body): array|bool|string|null
    {
        return mb_convert_encoding($body, "BIG5", 'UTF-8');
    }

    /**
     * @param string $response
     *
     * @return array
     */
    public function parseResponse(string $response): array
    {
        $data = [];
        $index = 0;
        foreach (explode("\r\n", $response) as $value) {
            if ($value === '') continue;

            $eq = strpos($value, '=');
            if ($eq !== false) {
                $data[$index][substr($value, 0, $eq)] = substr($value, $eq + 1);
            } else {
                $index = trim($value, '[]');
            }
        }
        return $data;
    }
}
