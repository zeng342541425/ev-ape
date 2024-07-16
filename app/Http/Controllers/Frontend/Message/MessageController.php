<?php

namespace App\Http\Controllers\Frontend\Message;

use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\User\My\UpdateAvatarRequest;
use App\Models\Common\Message;
use App\Models\Common\UserNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;


class MessageController extends BaseController
{

    /**
     * 最新消息列表
     * @param UpdateAvatarRequest $request
     * @return Response
     */
    public function list(Request $request): Response
    {

        $param = $request->only(['limit']);

        $model = Message::query()->select(['id', 'title', 'published_at', 'brief_introduction']);

        $webapp = $request->header('webapp');
        if ( in_array(strtolower($webapp), ['ios', 'android']) ) {
            $model->whereIn('type', [1, 4]);
        }

        if (strtolower($webapp) == 'web') {
            $model->whereIn('type', [2, 4]);
        }

        $model->where('published_at', '<=', date('Y-m-d H:i:s'))->where('status', 1);

        $list = $model->orderByDesc('published_at')->paginate($param['limit'] ?? 10);

        $data = $list->items();

        // if ($data) {
        //     foreach($data as $k => $v) {
        //         $data[$k]['content'] = View::make('common.template', ['template_content' => $v['content'], 'template_title' => $v['title']])->render();
        //         // unset($data[$k]['content']);
        //     }
        // }

        return $this->success([
            'list' => $data,
            'total' => $list->total()
        ]);

    }

    /**
     * 最新消息列表
     * @param UpdateAvatarRequest $request
     * @return Response
     */
    public function notices(Request $request): Response
    {

        $param = $request->only(['limit']);

        $user = $request->user();

        $model = UserNotice::query()->select(['id', 'title', 'brief_introduction' ,'published_at', 'reading']);

        $model->where('user_id', $user['id']);

        $list = $model->orderByDesc('published_at')->paginate($param['limit'] ?? 10);

        $data = $list->items();

        return $this->success([
            'list' => $data,
            'total' => $list->total()
        ]);

    }

    /**
     * 推播詳情
     * @param UpdateAvatarRequest $request
     * @return Response
     */
    public function noticeDetail(Request $request): Response
    {

        $message_id = $request->get('id',0);
        $object_id = $request->get('object_id',0);

        $user = $request->user();

        $select = ['id', 'title', 'brief_introduction', 'content' ,'published_at', 'reading'];


        if (!empty($message_id)){
            $ex = UserNotice::query()->select($select)->where('user_id', $user['id'])->where('id', $message_id)->first();
        }else{
            $ex = UserNotice::query()->select($select)->where('user_id', $user['id'])->where('object_id', $object_id)->first();
        }

        if ($ex && $ex['reading'] == 0) {
            $ex->update([
                'reading' => 1
            ]);
        }

        return $this->success(['info' => $ex ?: new \stdClass()]);

    }

    /**
     * 未讀數量
     * @param UpdateAvatarRequest $request
     * @return Response
     */
    public function noticeNumber(Request $request): Response
    {

        $user = $request->user();

        $count = UserNotice::query()->where('user_id', $user['id'])->where('reading', 0)->count();

        return $this->success(['number' => $count ? $count : 0]);

    }

    /**
     * 最新消息詳情
     * @param UpdateAvatarRequest $request
     * @return Response
     */
    public function messageDetail(Request $request): Response
    {

        $message_id = $request->get('id');

        // $user = $request->user();

        $model = Message::query()->select(['id', 'title', 'published_at', 'content', 'brief_introduction']);

        $ex = $model->where('id', $message_id)->where('published_at', '<=', date('Y-m-d H:i:s'))->where('status', 1)->first();

        if ($ex) {
            $webapp = $request->header('webapp');
            if (strtolower($webapp) == 'web') {
                $ex['content'] = View::make('common.template', ['template_content' => $ex['content'], 'template_title' => $ex['title']])->render();
            } else {
                $ex['content'] = View::make('common.app_template', ['template_content' => $ex['content'], 'template_title' => $ex['title']])->render();
            }

        }

        return $this->success(['info' => $ex ?: new \stdClass()]);

    }


}
