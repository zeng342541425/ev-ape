<?php

namespace App\Http\Controllers\Frontend\Brand;

use App\Http\Controllers\Frontend\BaseController;
use App\Models\Parking\Brand;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class BrandController extends BaseController
{

    /**
     * 車用品牌
     * @return Response
     */
    public function index(Request $request): Response
    {

        $list = Brand::query()->select('id', 'brand_name')->orderBy('brand_name')->get()->toArray();

        $user = $request->user();

        $user_brand_id = $user['brand_id'];

        if ($list && $user_brand_id > 0) {
            $u_brand = [];
            foreach($list as $k => $v) {
                if ($v['id'] == $user_brand_id) {
                    $u_brand = $v;
                    unset($list[$k]);
                    break;
                }
            }

            if ($u_brand) {
                array_unshift($list, $u_brand);
            }

        }

        return $this->success(['list' => $list]);

    }

}
