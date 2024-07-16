<?php

namespace App\Http\Controllers\Frontend\Parking;

use App\Http\Controllers\Frontend\BaseController;
use App\Models\Parking\ChargingPower;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class PowerController extends BaseController
{

    /**
     * 充電功率/規格
     * @return Response
     */
    public function index(): Response
    {

        $list = ChargingPower::query()->select('id','value', 'type')->get()->toArray();

        $res = [];
        foreach($list as $v) {
            if ($v['type'] == 1) {
                $res['power_list'][] = [
                    'id' => $v['id'],
                    'value' => $v['value'],
                ];
            } elseif ($v['type'] == 2) {
                $res['specification_list'][] = [
                    'id' => $v['id'],
                    'value' => $v['value'],
                ];
            }
        }
        return $this->success($res);

    }


}
