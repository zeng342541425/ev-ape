<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Common\Privacy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrivacyController extends Controller
{


    /**
     * 會員權益聲明和隱私權條款
     * @return Response
     */
    public function index(): Response
    {

        $model = Privacy::query();

        $data = $model->get(['url', 'type'])->toArray();

        return $this->success(['list' => $data]);

    }

    /**
     * 更新
     *
     * @param Request $request
     * @return Response
     */
    public function update(Request $request): Response
    {

        $request->validate([
            'url' => ['required', 'url']
        ]);
        $url = $request->post('url', '');

        if (strtolower(pathinfo($url)['extension']) != 'pdf') {
            return $this->error('請上傳PDF檔案');
        }

        $item = Privacy::query()->first();

        if (!$item) {
            $item = Privacy::query()->create([
                'url' => $url,
                'type' => 3
            ]);

        } else {

            if (!Privacy::query()->update(['url' => $url])) {
                return $this->error(__('message.common.update.fail'));
            }
        }

        return $this->success();
    }


}
