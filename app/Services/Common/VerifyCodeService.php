<?php

namespace App\Services\Common;

use Exception;

class VerifyCodeService
{
    // 客戶端類型
    const CLIENT_STORE = 'store';  // 店家後台
    const CLIENT_ADMIN = 'admin';  // 總後台
    const CLIENT_WEBAPI = 'webapi'; // web & api

    // 渠道
    const CHANNEL_EMAIL = 'email'; // 郵箱
    const CHANNEL_SMS = 'sms'; // 簡訊

    // 字符類型
    const CHATS_TYPE_NUMBER = 1; // 數字
    const CHATS_TYPE_ALPHA_LOWER = 2; // 字母小寫
    const CHATS_TYPE_ALPHA_UPPER = 3; // 字母大寫
    const CHATS_TYPE_ALPHA_CASE = 4; // 字母大小寫
    const CHATS_TYPE_ALPHA_NUM = 5; // 字母大小寫+數字

    /**
     * @var string 渠道
     */
    public $channel = '';

    /**
     * @var string 客戶端
     */
    public $client = '';

    /**
     * @var string 類型
     */
    public $type = '';

    /**
     * @var string 帳號
     */
    public $account = '';

    /**
     * @var string 驗證碼
     */
    public $code = '';

    /**
     * @var string 分隔符
     */
    public $separator = '|';

    /**
     * @var array 驗證碼配置
     */
    public $config = [
        'length' => 6,     // 長度
        'type' => self::CHATS_TYPE_NUMBER, // 類型
        'add_chars' => '', // 增加字符
        'ttl' => 600, // 緩存時間 單位：秒
        'interval' => 0, // 間隔時間 單位：秒 0無線
    ];

    public function __construct($channel, $client = '')
    {
        $this->channel = $channel;
        $this->client = $client;
    }

    /**
     * 簡訊
     * @param string $client
     * @return VerifyCodeService
     */
    public static function sms($client = '')
    {
        return new self(self::CHANNEL_SMS, $client);
    }

    /**
     * 郵箱
     * @param string $client
     * @return VerifyCodeService
     */
    public static function email($client = '')
    {
        return new self(self::CHANNEL_EMAIL, $client);
    }

    /**
     * 設置選項
     * @param array $options
     * @return $this
     */
    public function options(array $options = [])
    {
        foreach ($options as $key => $val) {
            $this->{$key} = $val;
        }
        return $this;
    }

    /**
     * 店家後台
     * @return $this
     */
    public function store()
    {
        $this->client = self::CLIENT_STORE;
        return $this;
    }

    /**
     * 總後台
     * @return $this
     */
    public function admin()
    {
        $this->client = self::CLIENT_ADMIN;
        return $this;
    }

    /**
     * web & api
     * @return $this
     */
    public function webapi()
    {
        $this->client = self::CLIENT_WEBAPI;
        return $this;
    }

    /**
     * 設置客戶端
     * @param $client
     * @return $this
     */
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * 設置類型
     * @param string $type 類型
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * 設置配置
     * @param $config
     * @return $this
     */
    public function setConfig($config = [])
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * 設置帳號
     * @param string $account 帳號
     * @return $this
     */
    public function setAccount($account)
    {
        $this->account = $account;
        return $this;
    }

    /**
     * 獲取 key
     * @return string
     */
    public function getKey()
    {
        return implode($this->separator, [
            $this->channel,
            $this->client,
            $this->type,
            $this->account,
        ]);
    }

    /**
     * 獲取驗證碼
     * @param $code
     * @return false|mixed|string
     * @throws Exception
     */
    public function getCode($code = '')
    {
        $this->code = $code;
        return $code ?: $this->generateCode();
    }

    /**
     * 生成驗證碼
     * @return false|string
     * @throws Exception
     */
    public function generateCode()
    {
        $random_code = $this->randomCode($this->config['length'], $this->config['type'], $this->config['add_chars']);
        $this->code = $random_code;
        return $random_code;
    }

    /**
     * 驗證驗證碼
     * @param $code
     * @param $is_clear
     * @return bool
     * @throws Exception
     */
    public function checkCode($code, $is_clear = true)
    {
        $universalCode = $this->getUniversalCode();
        if (!empty($universalCode) && $code == $universalCode) {
            return true;
        }

        $data = $this->getCache();
        if (empty($data['code'])) {
            return false;
        }
        if ($data['code'] !== $code) {
            return false;
        }

        if ($is_clear) {
            $this->clearCache();
        }
        return true;
    }

    /**
     * 萬能碼
     * @return mixed
     */
    public function getUniversalCode()
    {
        if (config('app.env') == "production") {
            return null;
        }
        return env('VERIFY_UNIVERSAL_CODE', null);
    }

    /**
     * 獲取緩存
     * @param $key
     * @return \Illuminate\Contracts\Cache\Repository|mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getCache($key = '')
    {
        $key = $key ?: $this->getKey();
        return cache()->get($key);
    }

    /**
     * 設置緩存
     * @param $key
     * @param $data array 額外數據
     * @return array
     * @throws Exception
     */
    public function setCache($key = '', $data = [])
    {
        $key = $key ?: $this->getKey();
        if (!empty($this->config['interval'])) { // 驗證發送間隔
            $cache_data = $this->getCache($key);
            if (!empty($cache_data['timestamp']) && $cache_data['timestamp'] + $this->config['interval'] > time()) {
                throw new Exception('發送頻繁，請稍後再試');
            }
        }
        $data = array_merge($data, [
            'code' => $this->getCode(),
            'timestamp' => time()
        ]);
        cache()->put($key, $data, $this->config['ttl']);
        return $data;
    }

    /**
     * 清除緩存
     * @param $key
     * @return void
     * @throws Exception
     */
    public function clearCache($key = '')
    {
        cache()->forget($key ?: $this->getKey());
    }

    /**
     * 隨機驗證碼
     * @param int $length
     * @param int $type
     * @param string $addChars
     * @return false|string
     * @throws Exception
     */
    public function randomCode(int $length = 6, int $type = self::CHATS_TYPE_NUMBER, string $addChars = '')
    {
        $str = '';
        switch ($type) {
            case self::CHATS_TYPE_NUMBER:
                $chars = str_repeat('0123456789', 3) . $addChars;
                break;
            case self::CHATS_TYPE_ALPHA_LOWER:
                $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case self::CHATS_TYPE_ALPHA_UPPER:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
                break;
            case self::CHATS_TYPE_ALPHA_CASE:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            default:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789' . $addChars;
                break;
        }
        if ($length > 10) {
            $chars = $type == 1 ? str_repeat($chars, $length) : str_repeat($chars, 5);
        }
        if ($type != 4) {
            $chars = str_shuffle($chars);
            $str = substr($chars, 0, $length);
        } else {
            for ($i = 0; $i < $length; $i++) {
                $str .= mb_substr($chars, floor(random_int(0, mb_strlen($chars, 'utf-8') - 1)), 1);
            }
        }
        return $str;
    }

}

