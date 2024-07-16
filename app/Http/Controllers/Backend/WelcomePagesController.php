<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\WelcomePages\UpdateRequest;
use App\Models\Common\WelcomePages;
use Symfony\Component\HttpFoundation\Response;

class WelcomePagesController extends Controller
{
    /**
     * 列表
     *
     * @return Response
     */
    public function list(): Response
    {

        $list = WelcomePages::query()->get()->toArray();

        return $this->success([
            'list' => $list
        ]);
    }

    /**
     * 更新
     *
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(UpdateRequest $request): Response
    {
        $param = $request->only([
            'image_url', 'display_time',
        ]);

        $item = WelcomePages::query()->first();

        if (!$item) {
            WelcomePages::query()->create($param);
        } else {
            if (!$item->update($param)) {
                return $this->error(__('message.common.update.fail'));
            }
        }

        return $this->success();
    }

    /**
     * 刪除
     *
     * @return Response
     */
    public function delete(): Response
    {

        $item = WelcomePages::query()->first();

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
