<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\User\Register\CodeRequest;
use App\Http\Requests\Frontend\User\Register\RegisterRequest;
use App\Jobs\RegularPushJob;
use App\Models\Common\PhoneCode;
use App\Models\Frontend\User\User;
use App\Services\Common\PhoneCodeService;
use App\Services\Common\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;


class RegisterController extends BaseController
{

    /**
     * 發送驗證碼
     *
     * @param CodeRequest $request
     * @return Response
     */
    public function sendCode(CodeRequest $request): Response
    {

        try {
            $phone = $request->get('phone', '');
            $code_key = $request->get('code_key', 'register');

            // 如果是提交註冊
            if ($code_key == 'register') {
                $r = $this->checkRegistration($phone);
                if ($r == 1) {
                    // 此帳號已被註冊
                    return $this->error('此帳號已被註冊');
                }
            } else {
                if (!User::query()->where('phone', $phone)->exists()) {
                    return $this->error('手機格式錯誤');
                }
            }


            $data = ['code_key' => $code_key, 'phone' => $phone];
            return (new PhoneCodeService)->send($data);

        } catch (\Throwable $e) {

            return $this->error($e->getMessage());
        }

    }

    /**
     * 驗證找回密碼驗證碼
     *
     * @param string $phone
     * @return int
     */
    protected function checkRegistration(string $phone): int
    {

        // 檢測手機號碼是否註冊了
        $user = User::query()->where('phone', $phone)->exists();

        return $user ? 1 : -1;

    }

    /**
     * 驗證找回密碼驗證碼
     *
     * @param Request $request
     * @return Response
     */
    public function check(Request $request): Response
    {
        try {
            $phone = $request->get('phone', '');
            $code = $request->get('code', '');
            $code_key = $request->get('code_key', 'register');

            $data = ['code_key' => $code_key, 'phone' => $phone, 'code' => $code];
            return (new PhoneCodeService)->checkAndResponse($data);

        } catch (\Throwable $e) {

            return $this->error($e->getMessage());
        }
    }

    /**
     * 註冊
     * @param RegisterRequest $request
     * @return Response
     */
    public function register(RegisterRequest $request): Response
    {
        $param = $request->only(['name', 'phone', 'password', 'code']);

        $phoneCodeService = new PhoneCodeService();
        $data = ['code_key' => 'register', 'phone' => $param['phone'], 'code' => $param['code']];
        $res = $phoneCodeService->detail($data);

        // 驗證碼錯誤
        // if (!$res || $res->status != 1 || $res->used != 0) {
        //     return $this->error('驗證碼錯誤');
        // }

        if (!$res || $res->used != 0) {
            return $this->error('驗證碼錯誤');
        }

        // 驗證 手機號碼 唯一
        $exist = User::query()->where('phone', $param['phone'])->first();
        if ($exist) {
            return $this->error('此帳號已被註冊');
        }

        $param['password'] = Hash::make($param['password']);

        $config = config('evape');
        $register_point = $config['register_points'] ?? 0;
        $param['points'] = $register_point;
        $param['avatar'] = $config['avatar_list'][0]['url'];
        $param['avatar_type'] = $config['avatar_list'][0]['avatar_type'];

        DB::beginTransaction();

        try {

            $user = User::query()->create($param);

            if (!PhoneCode::query()->where('id', $res->id)->update(['used' => 1])) {
                throw new \Exception('註冊失敗');
            }

            DB::commit();

            $r = (new UserService())->login($request->header('webapp'), $request->header('device', ''), $user);
            if (!$r) {
                return $this->error(__('auth.failed'));
            }

            $r['points'] = $register_point;
            // return $this->success($res, __('auth.login_success'));

            RegularPushJob::dispatch($user['id'], 'vip_sign_in');

            return $this->success($r, '註冊成功');

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

}
