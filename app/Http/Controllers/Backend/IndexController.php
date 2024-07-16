<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Brands\CreateRequest;
use App\Http\Requests\Backend\Brands\ListRequest;
use App\Http\Requests\Backend\Brands\IdRequest;
use App\Http\Requests\Backend\Brands\UpdateRequest;
use App\Http\Requests\Backend\System\FileSystem\UploadsRequest;
use App\Models\Common\Region;
use App\Services\Common\UploadFileService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends Controller
{


    /**
     * 地區
     * @return Response
     */
    public function index(): Response
    {

        $model = Region::query();

        // 關聯子級地區
        $model->with(['villages:id,name,pid']);

        $data = $model->where(['pid' => 0])->orderBy('zip_code')->get(['id', 'name'])->toArray();

        return $this->success(['list' => $data]);

    }

    /**
     * 上傳圖片
     *
     * @param UploadsRequest $request
     * @return Response
     */
    public function upload(Request $request): Response
    {

        $request->validate([
            'field' => 'required'
        ]);
        $file = $request->file('file');
        $field = $request->get('field');

        $upload_service = new UploadFileService();
        try {

            $folder = $field.'/'.date('Ym');

            $path = $upload_service->setFile($file)->isFileValid()->upload($folder);

            return $this->success($path);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

    }

}
