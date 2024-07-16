<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Frontend\BaseController;
use App\Models\Common\UserFirebase;
use App\Services\Common\FirebaseService;
use Illuminate\Http\Request;
use Kreait\Firebase\Exception\FirebaseException;
use Symfony\Component\HttpFoundation\Response;


class FirebaseController extends BaseController
{

    /**
     * 綁定firebase token
     * @param Request $request
     * @return Response
     */
    public function bind(Request $request): Response
    {

        $request->validate([
            'firebase_token' => 'required',
        ]);

        $user = $request->user();

        $firebase_token = $request->post('firebase_token', '');

        $device_id = $request->header('device-id');
        // UserFirebase::query()->where('device_id', $device_id)->where('user_id', $user['id'])->delete();

       // (new FirebaseService())->unsubTopics([$firebase_token]);
        UserFirebase::query()->where('firebase_token', $firebase_token)->delete();

        if ($user){
            UserFirebase::query()->create([
                'user_id' => $user['id'],
                'device_id' => $device_id,
                'firebase_token' => $firebase_token,
            ]);
        }



        try {
            (new FirebaseService())->subTopics($firebase_token);
        } catch (\Throwable $e) {
        }

        return $this->success();

    }


    /**
     * 解綁
     * @param Request $request
     * @return Response
     */
    public function unbind(Request $request): Response
    {

        $request->validate([
            'firebase_token' => 'required',
        ]);

       // $user = $request->user();

        $firebase_token = $request->post('firebase_token', '');

       // (new FirebaseService())->unsubTopics([$firebase_token]);

        // $device_id = $request->header('device-id');
        UserFirebase::query()->where('firebase_token', $firebase_token)->delete();


        return $this->success();

    }

}
