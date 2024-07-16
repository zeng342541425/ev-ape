<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Constants\Constant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Admin\Notification\ListRequest;
use App\Http\Requests\Backend\Admin\Notification\ReadRequest;
use App\Http\Requests\Backend\Admin\Notification\SendRequest;
use App\Models\Backend\Admin\Admin;
use App\Models\Backend\System\Notifications;
use App\Notifications\Message;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{

    /**
     * 獲取通知列表
     *
     * @param ListRequest $request
     * @return Response
     */
    public function list(ListRequest $request): Response
    {
        $param = $request->all();

        $admin = $request->user();

        $query = Notifications::user($admin);

        if (isset($param['message']) && $param['message'] != '') {
            $query->like('data->message', $param['message']);
        }

        if (!empty($param['is_read'])) {
            if ($param['is_read'] == Constant::COMMON_IS_NO) {
                $query->whereNull('read_at');
            } else {
                $query->whereNotNull('read_at');
            }
        }

        if (!empty($param['created_at'])) {
            $query->timeBetween('created_at', $param['created_at']);
        }

        if (!empty($param['sort']) && !empty($param['order'])) {
            $query->orderBy($param['sort'], order_direction($param['order']));
        } else {
            $query->orderByDesc('created_at');
        }

        $list = $query->paginate($param['limit']);

        $list->each(function ($item) {
            $item->append([
                'plain_text'
            ]);
        });


        return $this->success([
            'list' => $list->items(),
            'total' => $list->total(),
        ]);
    }

    /**
     * 通知詳情
     *
     * @param Request $request
     * @return Response
     */
    public function detail(Request $request): Response
    {
        $id = $request->post('id');

        $admin = $request->user();

        $item = Notifications::user($admin)->find($id);
        if (!$item) {
            return $this->error(__('message.common.search.fail'));
        }

        if (!$item->read_at) {
            $item->update(['read_at' => now()]);
        }
        return $this->success([
            'item' => $item
        ], __('message.common.search.success'));
    }

    /**
     * 未讀通知數
     * @param Request $request
     * @return Response
     */
    public function unReadCount(Request $request): Response
    {

        $count = $request->user()->unreadNotifications()->count('id');
        return $this->success([
            'count' => $count
        ], __('message.common.search.success'));
    }

    /**
     * 全部已讀
     * @param Request $request
     * @return Response
     */
    public function allRead(Request $request): Response
    {
        $count = $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->success([
            'count' => $count
        ], __('message.common.update.success'));
    }


    /**
     * 標記已讀
     * @param ReadRequest $request
     * @return Response
     */
    public function read(ReadRequest $request): Response
    {
        $count = $request->user()->unreadNotifications()
            ->whereIn('id', $request->post('ids'))
            ->update(['read_at' => now()]);
        return $this->success([
            'count' => $count
        ], msg: __('message.common.update.success'));
    }


    /**
     * 發送通知
     *
     * @param SendRequest $request
     * @return Response
     */
    public function send(SendRequest $request): Response
    {
        $admin = $request->user();
        $param = $request->only([
            'message', 'admins'
        ]);
        $query = Admin::status(Constant::COMMON_STATUS_ENABLE)
            ->where('id', '!=', $admin->id);
        if (!empty($param['admins'])) {
            $query->whereIn('id', $param['admins']);
        }

        $query->get()->each(function (Admin $user) use ($param, $admin): void {
            $user->notify(new Message([
                'form' => $admin->name,
                'message' => $param['message']
            ]));
        });

        return $this->success(msg: __('message.common.create.success'));
    }

    /**
     * 獲取可通知的後臺管理員
     * @param Request $request
     * @return Response
     */
    public function admins(Request $request): Response
    {
        $admins = Admin::status(Constant::COMMON_STATUS_ENABLE)
            ->where('id', '!=', $request->user()->id)
            ->select(['id', 'name'])
            ->get();
        return $this->success([
            'admins' => $admins
        ], __('message.common.search.success'));
    }

}
