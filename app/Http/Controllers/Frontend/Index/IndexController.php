<?php

namespace App\Http\Controllers\Frontend\Index;

use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Backend\System\FileSystem\UploadsRequest;
use App\Http\Requests\Frontend\Index\ContactUsRequest;
use App\Models\Common\Advertisement;
use App\Models\Common\Banner;
use App\Models\Common\ContactUs;
use App\Models\Common\GuidePage;
use App\Models\Common\Privacy;
use App\Models\Common\Region;
use App\Models\Common\VersionControl;
use App\Models\Common\WebsiteInfo;
use App\Models\Common\WelcomePages;
use App\Services\Common\UploadFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;


class IndexController extends BaseController
{

    public function test()
    {
        // $kot = new Kot();
        // // $resUrl = route('sms_response');
        // $resUrl = 'https://api.evape.casaloma.cc/api/sms_response';
        // $sms_config = config("sms.register");
        //
        // $code = Common::nonceRandom(4, 2);
        // $content = str_replace('{code}', $code, $sms_config['content']);
        // $phone = '0906319727';
        // var_dump($content);
        // // var_dump($resUrl);
        // $kot->setResponseUrl($resUrl)->send($phone, $content);
    }

    public function smsResponse(Request $request)
    {
        $data = $request->all();
        Log::info('sms_response:', ['data' => $data]);
        // $kot->setResponseUrl()->send($this->phone, $this->content);
    }

    /**
     * 車用品牌
     * @return Response
     */
    public function index(): Response
    {

        $model = Region::query();

        // 關聯子級地區
        // $model->with(['villages:id,name,pid,latitude,longitude']);
        $model->with(['villages' => function($q) {

            $q->select('id', 'name', 'pid', 'latitude', 'longitude')->orderBy('zip_code');
        }]);

        $data = $model->where(['pid' => 0])->orderBy('zip_code')->get(['id', 'name'])->toArray();

        return $this->success(['list' => $data]);

    }

    public function contactUs(ContactUsRequest $request): Response
    {
        $data = $request->only([
            'company', 'full_name', 'job_titles', 'telephone', 'email', 'demand', 'description'
        ]);

        ContactUs::query()->create($data);

        return $this->success();
    }

    /**
     * 上傳圖片
     *
     * @param UploadsRequest $request
     * @return Response
     */
    public function upload(Request $request): Response
    {

        $file = $request->file('file');
        $field = 'frontend';

        $upload_service = new UploadFileService();
        try {
            // $upload_service->setFile($file)->isImage();

            $folder = $field.'/'.date('Ym');

            $path = $upload_service->setFile($file)->isImage()->upload($folder);

            return $this->success(['url' => $path['url']]);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

    }

    /**
     * 首頁輪播圖
     * @return Response
     */
    public function bannerList(): Response
    {

        $model = Banner::query()->where('status', 1)
            ->where('starting_time', '<=', date('Y-m-d H:i:s'))
            ->where('ending_time', '>=', date('Y-m-d H:i:s'));

        $data = $model->orderBy('sort')->get(['image_url'])->toArray();

        return $this->success(['list' => $data]);

    }

    /**
     * 會員權益聲明和隱私權條款
     * @param Request $request
     * @return Response
     */
    public function documents(Request $request): Response
    {

        $request->validate([
            'type' => ['required', 'in:1,2,3,4']
        ]);


        $type = $request->get('type', 0);

        if ($type != 3) {
            $model = WebsiteInfo::query();
            $model->where('type', $type);

            $data = $model->get(['type', 'content'])->first();

            if ($data) {
                $webapp = $request->header('webapp');
                //1:隱私權政策與服務條款檔案;2:會員權益聲明；4: 新手會員說明。
                $type_map = [
                    1 => '隱私權政策與服務條款',
                    2 => '會員權益聲明',
                    4 => '新手會員說明',
                ];

                if (strtolower($webapp) == 'web') {
                    $data['content'] = View::make('common.template',
                        ['template_content' => $data['content'] ?? '', 'template_title' => $type_map[$data['type']] ?? ''])->render();
                } else {
                    $data['content'] = View::make('common.app_template',
                        ['template_content' => $data['content'] ?? '', 'template_title' => $type_map[$data['type']] ?? ''])->render();
                }

            }

        }

        if ($type == 3) {
            $privacy_model = Privacy::query();
            $info = $privacy_model->select('type', 'url as content')->first();
            if ($info) {
                $data = $info;
            }
        }

        return $this->success(['info' => $data]);

    }

    /**
     * APP歡迎頁
     * @return Response
     */
    public function welcomePage(): Response
    {

        $data = WelcomePages::query()->select('image_url', 'display_time')->first();

        return $this->success(['info' => $data ?: new \stdClass()]);

    }

    /**
     * APP引導頁
     * @return Response
     */
    public function guidePage(): Response
    {

        $list = GuidePage::query()->select('image_url')->orderBy('sort')->get()->toArray();

        return $this->success(['list' => $list ? array_column($list, 'image_url') : []]);

    }

    /**
     * APP引導頁
     * @return Response
     */
    public function advertisement(): Response
    {

        $data = Advertisement::query()->where('status', 1)
            ->where('starting_time', '<=', date('Y-m-d H:i:s'))
            ->where('ending_time', '>=', date('Y-m-d H:i:s'))
            ->select(['image_url', 'name', 'link_type', 'link_value'])->first();

        return $this->success(['info' => $data ?: new \stdClass()]);

    }

    public function getVersion(Request $request): Response
    {

        $webapp = $request->header('webapp');
        $version = VersionControl::query()->select('version', 'min_version')->where('app_type', strtoupper($webapp))->first();
        if ($version) {
            return $this->success($version);
        }

        return $this->success();

    }

}
