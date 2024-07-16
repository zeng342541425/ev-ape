<?php

namespace App\Services\Common;


use App\Models\Common\PhoneCode;
use App\Traits\ReturnJson;
use App\Util\Sms\MitakeBtcSms;
use Illuminate\Http\JsonResponse;

class PhoneCodeService
{

    use ReturnJson;

    /**
     * 發送驗證碼
     *
     * @param array $data
     * @return JsonResponse
     */
    public function send(array $data = []): JsonResponse
    {

        if (isset($data['code_key']) && !empty($data['code_key'])) {

            $key = $data['code_key'];

            $sms_config = config("sms");
            $config =  $sms_config[$key] ?? $sms_config['register'];

            $type = $config['code_type'];
            $expire = $config['expired_time'];

            $insert_data['code_type'] = $type;
            $insert_data['phone'] = $data['phone'];

            // 第三方發送驗證碼代碼
            $debug = config('evape.phone_code_debug');

            $code = $debug ? '1234' : Common::generatedNonce(4, 2);
            $insert_data['code'] = $code;
            $insert_data['expired_time'] = time() + $expire;

            PhoneCode::query()->create($insert_data);

            // 調試模式，如果true, 不會發送sms
            if ( !$debug ) {
                // 對接第三方接口發送
                $content = str_replace('{code}', $code, $config['content']);

                $res = MitakeBtcSms::make()->smSend($data['phone'], $content);
                if (!isset($res[1]['statuscode']) || !in_array($res[1]['statuscode'], [0, 1, 2, 4])) {
                    return $this->error(msg: '簡訊發送失敗');
                }
            }

            return $this->success(null, '已發送');

        }

        return $this->error('簡訊發送失敗');

    }

    /**
     * @Description： 驗證手機驗證碼, 驗證成功後返回審核詳情
     * @param array $data
     * @return int 1:成功驗證；-1：驗證碼沒有找到；2：驗證碼已經被驗證過了；3：驗證碼已過期
     */
    public function check(array $data = []): int
    {

        $exists = $this->detail($data);

        // 如果存在就更新狀態
        if($exists) {

            // 如果狀態不為0，表示已經驗證過了
            if ( $exists->status == 1 ) {
                return 2;
            }

            // 如果已經過期
            if ( $exists->expired_time < time() ) {
                return 3;
            }

            PhoneCode::query()->where('id', $exists->id)->update(['status' => 1]);

            return 1;
        }

        return -1;

    }


    /**
     * 驗證驗證碼並且輸出結果
     *
     * @param array $data
     * @return JsonResponse
     */
    public function checkAndResponse(array $data = []): JsonResponse
    {

        $res = $this->check($data);

        switch ($res) {
            case 1:
                return $this->success();
                break;

            case 2:
                // 已經使用過了
                return $this->error('驗證碼錯誤');
                break;

            case 3:
                // 驗證碼過期
                return $this->error('驗證碼過期');
                break;

            default:
                return $this->error('驗證碼錯誤');
                break;
        }

    }


    /**
     * 通過手機號碼、驗證碼、驗證碼類型獲取數據
     *
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function detail(array $data = [])
    {
        if(isset($data['code_key']) && !empty($data['code_key']) && isset($data['phone']) && isset($data['code'])) {

            $key = $data['code_key'];

            $sms_config = config("sms");
            $config =  $sms_config[$key] ?? $sms_config['register'];

            // 取最近一條驗證碼進行驗證
            $where_data = [
                'phone' => $data['phone'],
                'code' => $data['code'],
                'code_type' => $config['code_type'],
                //'status' => $data['status'] ?? 0,
            ];

            return PhoneCode::query()->where($where_data)->orderBy('id', 'desc')->first();

        }

        return null;

    }

    public function SendSms($msg)
    {

        //SmsJob::dispatch($msg['phone'], $msg['content']);

    }


}
