<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\User\My\SendEmailRequest;
use App\Http\Requests\Frontend\User\My\UpdateAddressRequest;
use App\Http\Requests\Frontend\User\My\UpdateAvatarRequest;
use App\Http\Requests\Frontend\User\My\UpdateBackgroundRequest;
use App\Http\Requests\Frontend\User\My\UpdateEmailRequest;
use App\Http\Requests\Frontend\User\My\UpdateInfoRequest;
use App\Http\Requests\Frontend\User\My\UpdatePwdRequest;
use App\Mail\Notice;
use App\Models\Common\EmailCode;
use App\Models\Common\Region;
use App\Models\Frontend\User\User;
use App\Services\Common\EmailCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use App\Jobs\EmailJob;


class MyController extends BaseController
{

    /**
     * 修改大頭貼
     * @param UpdateAvatarRequest $request
     * @return Response
     */
    public function updateAvatar(UpdateAvatarRequest $request): Response
    {

        $user = $request->user();
        $avtar = $request->get('avatar');
        $avatar_type = $request->get('avatar_type');

        if ($avatar_type > 0) {
            $find = 0;
            $avatar_list = config('evape.avatar_list');
            foreach($avatar_list as $v) {
                if ($v['avatar_type'] == $avatar_type) {
                    $avtar = $v['url'];
                    $find = 1;
                    break;
                }
            }

            if ($find == 0) {
                return $this->error();
            }
        }


        User::query()->where('id', $user['id'])->update([
            'avatar' => $avtar,
            'avatar_type' => $avatar_type,
        ]);

        return $this->success();

    }

    /**
     * 修改背景圖片
     * @param UpdateBackgroundRequest $request
     * @return Response
     */
    public function updateBackground(UpdateBackgroundRequest $request): Response
    {

        $user = $request->user();
        $background = $request->get('background');

        User::query()->where('id', $user['id'])->update([
            'background' => $background
        ]);

        return $this->success();

    }

    /**
     * 修改個人資料
     * @param UpdateInfoRequest $request
     * @return Response
     */
    public function updateInfo(UpdateInfoRequest $request): Response
    {

        $user = $request->user();
        $data = $request->only(['gender', 'birthday', 'educate', 'address', 'region_id', 'village_id','brand_id']);

        if (empty($data['address']) || empty('region_id') || empty('village_id')) {
            unset($data['address'], $data['region_id'], $data['village_id']);
        } else {
            if (!Region::query()->where('id', $data['village_id'])->where('pid', $data['region_id'])->exists()) {
                return $this->error();
            }
        }

        // if (empty($data['gender'])) {
        //     unset($data['gender']);
        // }

        if (empty($data['birthday'])) {
            unset($data['birthday']);
        }

        if (empty($data['educate'])) {
            unset($data['educate']);
        }

        if (empty($data['brand_id'])) {
            unset($data['brand_id']);
        }

        User::query()->where('id', $user['id'])->update($data);

        return $this->success();

    }

    /**
     * 修改地址
     * @param UpdateAddressRequest $request
     * @return Response
     */
    public function updateAddress(UpdateAddressRequest $request): Response
    {

        $user = $request->user();
        $data = $request->only(['address', 'region_id', 'village_id']);

        if (!Region::query()->where('id', $data['region_id'])->exists()) {
            return $this->error();
        }

        if (!Region::query()->where('id', $data['village_id'])->where('pid', $data['region_id'])->exists()) {
            return $this->error();
        }

        User::query()->where('id', $user['id'])->update($data);

        return $this->success();

    }

    /**
     * 修改email
     * @param SendEmailRequest $request
     * @return Response
     */
    public function sendEmail(SendEmailRequest $request): Response
    {

        $data = $request->only(['email']);
        EmailCodeService::get_code($data['email']);

//        $__data = [
//            'username' => $request->user()['name'],
//            'code' => EmailCodeService::get_code($data['email']),
//        ];

       // Mail::to($data['email'])->send(new Notice("user_verify", $__data));
        //EmailJob::dispatch($data['email']);
        // (new EmailCodeService($data['email']))->send();

        return $this->success();

    }

    /**
     * 修改email
     * @param UpdateEmailRequest $request
     * @return Response
     */
    public function updateEmail(UpdateEmailRequest $request): Response
    {

        $user = $request->user();
        $email = $request->get('email');
        $code = $request->get('code');
        $data = $request->only(['email']);

        DB::beginTransaction();

        try {
            $detail = EmailCode::query()->where('email', $email)
                ->where('code', $code)
                ->where('status', 0)
                ->where('expired_time', '>=', time())
                ->orderBy('id', 'desc')->first();

            if ($detail) {
                User::query()->where('id', $user['id'])->update($data);

                EmailCode::query()->where('id', $detail['id'])->update(['status' => 1]);
            } else {
                throw new \Exception('驗證碼錯誤或已過期');
            }

            DB::commit();

            return $this->success();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 修改密碼
     * @param UpdatePwdRequest $request
     * @return Response
     */
    public function updatePwd(UpdatePwdRequest $request): Response
    {

        $user = Auth::user();
        $old_password = $request->get('old_password');
        $password = $request->get('password');

        // 檢查現在的密碼
        if (!Hash::check($old_password, $user['password'])) {
            return $this->error('密碼錯誤');
        }

        DB::beginTransaction();

        try {

            User::query()->where('id', $user['id'])->update([
                'password' => Hash::make($password),
            ]);

            // 登出
            $user->tokens()->delete();

            DB::commit();

            return $this->success();
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 頭像列表
     * @return Response
     */
    public function avatarList(): Response
    {

        return $this->success(['avatar_list' => config('evape.avatar_list')]);

    }

}
