<?php

namespace App\Http\Controllers\Frontend\User;

use App\Constants\Constant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\User\Login\LoginRequest;
use App\Http\Requests\Frontend\User\Register\ResetPwdRequest;
use App\Models\Common\PhoneCode;
use App\Models\Common\Region;
use App\Models\Frontend\User\User;
use App\Models\Order\Order;
use App\Models\Parking\Brand;
use App\Services\Common\PhoneCodeService;
use App\Services\Common\UserService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{

    /**
     * Get the guard info
     *
     * @return Response
     */
    public function me(): Response
    {
        $user = Auth::user();

        $res_fields = ['id', 'name', 'phone', 'avatar', 'avatar_type', 'background', 'points', 'gender', 'birthday', 'email',
            'region_id', 'region_name', 'village_id', 'village_name','address', 'educate', 'brand_name','brand_id'];

        $user['brand_name'] = Brand::query()->where('id',$user['brand_id'])->value('brand_name');


        if ($user['region_id'] > 0) {
            $region = Region::query()->where('id', $user['region_id'])->first();
            $user['region_name'] = $region['name'] ?? '';
        }

        if ($user['village_id'] > 0) {
            $region = Region::query()->where('id', $user['village_id'])->first();
            $user['village_name'] = $region['name'] ?? '';
        }

        return $this->success([
            'user_info' => $user->only($res_fields)
        ]);
    }

    /**
     * 登入
     *
     * @param LoginRequest $request
     * @return Response
     */
    public function login(LoginRequest $request): Response
    {
        $param = $request->only(['phone', 'password']);

        $user = User::query()->where('phone', $param['phone'])->first();

        if (!$user || !Hash::check($param['password'], $user->password)) {
            return $this->error(__('auth.failed'));
        }
        if ($user->status != Constant::COMMON_STATUS_ENABLE) {
            return $this->error(__('auth.disabled'), null, 403);
        }

        $res = (new UserService())->login($request->header('webapp'), $request->header('device', ''), $user);
        if (!$res) {
            return $this->error(__('auth.failed'));
        }

        return $this->success($res, __('auth.login_success'));
    }

    /**
     * Log the user out of the application.
     *
     * @return Response
     */
    public function logout(): Response
    {
        //刪除當前請求token
        auth()->user()->currentAccessToken()->delete();

        return $this->success();
    }

    /**
     * 修改密碼
     *
     * @param ResetPwdRequest $request
     * @return Response
     */
    public function resetPassword(ResetPwdRequest $request): Response
    {

        $phone = $request->get('phone', '');
        $code = $request->get('code', '');
        $pd = $request->get('password', '');

        if (empty($code)) {
            return $this->error();
        }

        $phoneCodeService = new PhoneCodeService();
        $data = ['code_key' => 'reset_passwd', 'phone' => $phone, 'code' => $code];
        $res = $phoneCodeService->detail($data);

        // 驗證碼錯誤
        if (!$res) {
            return $this->error();
        }

        if ($res->status == 1 && $res->used == 0) {
            // 檢測手機號碼是否註冊了
            $user = User::query()->where('phone', $phone)->first();
            if ($user) {
                DB::beginTransaction();

                try {
                    $update_data = [
                        'password' => Hash::make($pd),
                    ];
                    $r = User::query()
                        ->where('phone', $phone)
                        ->update($update_data);

                    if ($r) {
                        if (!PhoneCode::query()->where('id', $res->id)->update(['used' => 1])) {
                            throw new \Exception(__('messages.verification_code'));
                        }
                    }

                    $user->tokens()->delete();

                    DB::commit();

                    // UnbindFirebase::dispatch($user);

                    return $this->success();
                } catch (\Throwable $e) {
                    DB::rollBack();
                    return $this->error($e->getMessage());
                }
            }
        }

        return $this->error('重置密碼失敗');

    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return array
     */
    protected function respondWithTokenData(string $token): array
    {
        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard()->factory()->getTTL() * 60
        ];
    }
}
