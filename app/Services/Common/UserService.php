<?php

namespace App\Services\Common;

use App\Models\Common\UserFirebase;
use App\Models\Sanctum\PersonalAccessToken;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class UserService
{

    public function login($webapp, $device_name, $user): bool|array
    {
        $minutes = config('evape.frontend_login_expiration');
        if (strtolower($webapp) == 'web') {
            // 如果是web端，需要改變token時效
            $minutes = config('evape.web_frontend_login_expiration');
        }

        // 先刪除已經存在的token
        // PersonalAccessToken::query()
        //     ->where('tokenable_type', 'App\Models\Frontend\User\User')
        //     ->where('tokenable_id', $user->id)->whereNot('name', 'Web')
        //     ->delete();
        //
        // // 刪除firebase
        // $firebase_list = UserFirebase::query()->where('user_id', $user->id)->get()->toArray();
        // if ($firebase_list) {
        //     (new FirebaseService())->unsubTopics(array_column($firebase_list, 'firebase_token'));
        //     UserFirebase::query()->where('user_id', $user->id)->delete();
        // }

        $expiration = Carbon::now()->addMinutes($minutes);

        $token = $user->createToken($device_name, ['*'], $expiration)->plainTextToken;
        if (!$token) {
            return false;
        }

        return [
            'user_info' => $user->only(['id', 'name', 'phone', 'avatar', 'avatar_type', 'background']),
            // 'token_info' => $this->respondWithTokenData($token)
            'token_info' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => $minutes
            ]
        ];
    }

}
