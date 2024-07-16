<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Message\CreateRequest;
use App\Http\Requests\Backend\Message\ListRequest;
use App\Http\Requests\Backend\Message\IdRequest;
use App\Http\Requests\Backend\Message\UpdateRequest;
use App\Models\Common\Message;
use App\Models\Common\WebsiteInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class WebsiteController extends Controller
{
    /**
     * 列表
     *
     * @return Response
     */
    public function list(): Response
    {

        $data = WebsiteInfo::query()->get()->toArray();

        return $this->success([
            'list' => $data,
        ]);
    }

    /**
     * 詳情
     *
     * @param IdRequest $request
     * @return Response
     */
    public function detail(Request $request): Response
    {
        $id = $request->post('type', 1);

        $item = WebsiteInfo::query()->where('type', $id)->first();

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        return $this->success([
            'item' => $item
        ]);
    }

    /**
     * 更新
     *
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(Request $request): Response
    {
        $type = $request->post('type');
        $param = $request->only([
            'content'
        ]);

        $item = WebsiteInfo::query()->where('type', $type);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        $admin = Auth::user();
        $param['admin_id'] = $admin['id'];
        $param['admin_name'] = $admin['name'];
        $param['admin_username'] = $admin['username'];

        if (!$item->update($param)) {
            return $this->error(__('message.common.update.fail'));
        }

        return $this->success();
    }

    // 預覽
    public function preview(Request $request): Response
    {

        $content = $request->get('content');
        $title = $request->get('title', '預覽');

        // 1：官網預覽；2：手機預覽
        $type = $request->get('type', 1);

        $ex['preview_content'] = View::make('common.template', ['template_content' => $content, 'template_title' => $title])->render();
        if ($type == 2) {
            $ex['preview_content'] = View::make('common.app_template', ['template_content' => $content, 'template_title' => $title])->render();
        }


        return $this->success(['info' => $ex ?: new \stdClass()]);

    }
}
