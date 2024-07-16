<?php

namespace App\Util\Sms;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class Kot implements SmsInterface
{

    /**
     * 加密版本
     */
    const TLS_1_0 = 'tls-1.0';
    const TLS_1_2 = 'tls-1.2';

    // 狀態碼
    const STATUS_STR_DELIVERED = 'DELIVERED'; // 成功發送  確定該手機已收到簡訊且回應狀態。
    const STATUS_STR_EXPIRED = 'EXPIRED'; // 逾時未達  該門號手機一直未開機、或收不到訊號，系統已重複發送8~24小時，仍然無法傳送簡訊給該門號。
    const STATUS_STR_DELETED = 'DELETED'; // 刪除簡訊  指該簡訊違反台灣現有法規(禁用字)，此簡訊已被系統刪除，將不再發送。
    const STATUS_STR_UNDELIV = 'UNDELIV'; // 無法投遞  可能是簡訊收件夾已滿或無法於8~24小時內重複發送…等問題。
    const STATUS_STR_ACC_PTD = 'ACC PTD'; // 發送失敗   電信業者回覆告知發送失敗。此狀態為該簡訊送達門號有異常，例如:空號、停話、號碼不存在…等。※簡訊內容出現NCC規定【禁用字】時,將造成『全數發送失敗』※
    const STATUS_STR_UNKNOWN = 'UNKNOWN'; // 未知情形  此狀態為系統商與系統商之間資料交換失敗皆統稱未知情形。
    const STATUS_STR_REJECTD = 'REJECTD'; // 拒收簡訊  該接收門號拒收簡訊。
    const STATUS_STR_SYNTAXE = 'SYNTAXE'; // 語法錯誤  發送簡訊語法錯誤，編碼錯誤或發送號碼為付費手機門號(0948)…等。

    /**
     * 配置
     *
     * @var array $config = [
     *     'username' => '',
     *     'password' => '',
     * ];
     */
    protected $config = [];

    /**
     * 加密版本
     * @var string
     */
    protected $tls = self::TLS_1_0;

    /**
     * Api 域名
     *
     * @var array[]
     */
    protected $apiDomains = [
        self::TLS_1_0 => 'https://api.kotsms.com.tw',
        self::TLS_1_2 => 'https://api2.kotsms.com.tw',
    ];


    /**
     * 回應地址
     *
     * 發送簡訊是否成功的狀態回報網址, 若不宣告此參數時為不回報。
     * @var string
     *
     * Response 內文說明 :
     * Method: Post
     *
     * kmsgid = 簡訊發送編號 (請以此編號核對發送結果)
     * dstaddr = 接收門號
     * dlvtime = 電信系統發出時間
     * donetime = 手機用戶端回報狀態時間(包含成功發送,無法投遞….等狀態)
     * statusstr = 狀態碼
     *
     */
    protected $responseUrl = '';


    /**
     * @param $config
     */
    public function __construct($config = null)
    {
        $this->config = $config ?? config('sms.kot');
    }

    /**
     * 設置 - 加密版本
     * @param $tls
     * @return $this
     */
    public function setTls($tls)
    {
        $this->tls = $tls;
        return $this;
    }

    /**
     * 設置 - 回應地址
     * @param $responseUrl
     * @return $this
     */
    public function setResponseUrl($responseUrl)
    {
        $this->responseUrl = $responseUrl;
        return $this;
    }

    /**
     * 獲取 Http 客戶端
     * @return Client
     */
    protected function getHttpClient()
    {
        return new Client([
            'base_uri' => $this->apiDomains[$this->tls],
            'verify' => false,
        ]);
    }

    /**
     * 轉為 Big5 編碼
     * @param $body
     * @return array|false|string|string[]|null
     */
    protected function toBig5($body)
    {
        return mb_convert_encoding($body, "BIG5", 'UTF-8');
    }

    /**
     * 查詢參數
     * @return array
     */
    protected function queryParam()
    {
        $query = [
            'username' => $this->config['username'],
            'password' => $this->config['password'],
        ];
        if ($this->responseUrl) {
            $query['response'] = $this->responseUrl;
        }
        return $query;
    }

    /**
     * 發送
     * @param string $phone 手機號碼
     * @param string $content 內容
     *  簡訊內容(BIG5)
     *  ※利用api傳送資料時，請將所有傳送資料都以url編碼，可避免因特殊字元，使得傳送裁斷或顯示異常。
     *
     * @param int|string $delay 預約發送時間
     *  dlvtime 為簡訊預約發送時間，可使用,YYYY/MM/DD hh24:mm:ss,格式設定
     *  建議設定為"0"即時發送。
     *  若有預約簡訊需求請於發送時間到達時再傳入簡訊王主機，可解決無法修改預約內容以及預約時間限制等問題。
     *  若不宣告此參數，系統將判定為即時發送，但發送效率速度較設定為"0"值時較慢。
     *  ※若使用預約發送YYYY/MM/DD hh24:mm:ss,格式設定時請將所有傳送資料都以url編碼傳送(“日”與”時”之間的空格須轉碼為%20否則無法預約時、分、秒)
     *
     * @param int|string $valid 發送簡訊的有效期限
     *  ※vldtime為發送簡訊的有效期限:
     *  當接收手機未開機時,系統會重複再次發送簡訊給接收門號，若您的簡訊內容有時效性，您可設定若超過設定時間後就不再重複發送。
     *  有效期限可使用,YYYY/MM/DD hh24:mm:ss,格式設定或使用” 秒數”設定
     *  可設定秒數為 1800秒(30分鐘)~28800秒(八小時) 最小值建議1800秒最大值最多為八小時，若超過八小時系統還是會將有效時間判定為八小時。
     *  若vldtime設定為0秒時有效時間將依簡訊中心流量設定值配送約4~24小時。
     *  若vldtime不宣告此參數時，有效期限為預設值8小時。
     *
     * @param bool $lot 是否大量
     *  true 是
     *  false 否[默認]
     *
     * @return array [
     *      "kmsgid" => "385784495"
     * ]
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function send(string $phone,string $content,array $options = []): bool
    {
       // $delay = 0, $valid = 0, $lot = false

        $query = array_merge($this->queryParam(), [
            'dstaddr' => $phone,
            'smbody' => $this->toBig5($content),
            'dlvtime' => $options['delay']??0, //延遲
            'vldtime' => $options['valid']??0
        ]);

        if (empty($options['valid'])){
            $uri = '/kotsmsapi-1.php';
        }else{
            $uri = '/kotsmsapi-2.php';
        }

        try {
            $client = $this->getHttpClient();
            $response = $client->get($uri, [
                'query' => $query
            ]);
            parse_str(trim($response->getBody()->getContents()), $result);
            Log::info('KotSms api-1 response', [
                'query' => $query,
                'result' => $result
            ]);
            return true;
        }catch (\Throwable $throwable){
            report($throwable);
        }
        return false;
    }
}
