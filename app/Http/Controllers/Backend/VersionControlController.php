<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\VersionControl\CreateRequest;
use App\Http\Requests\Backend\VersionControl\ListRequest;
use App\Http\Requests\Backend\VersionControl\IdRequest;
use App\Http\Requests\Backend\VersionControl\UpdateRequest;
use App\Models\Common\VersionControl;
use Symfony\Component\HttpFoundation\Response;

class VersionControlController extends Controller
{
    /**
     * 列表
     *
     * @return Response
     */
    public function list(): Response
    {

        $query = VersionControl::query();


        $query->orderByDesc('created_at');

        $list = $query->get()->toArray();

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
        $id = $request->post('id');
        $param = $request->only([
            'app_type', 'version', 'min_version',
        ]);

        $item = VersionControl::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!$item->update($param)) {
            return $this->error(__('message.common.update.fail'));
        }

        return $this->success();
    }

}
