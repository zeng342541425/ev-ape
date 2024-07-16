<?php

namespace App\Http\Controllers\Frontend\Parking;

use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\Fault\PileRequest;
use App\Jobs\EmailJob;
use App\Mail\Notice;
use App\Models\Common\FaultCategories;
use App\Models\Common\Faults;
use App\Models\Parking\ChargingPile;
use App\Models\Parking\ParkingLot;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;


class FaultController extends BaseController
{

    /**
     * 提交客服問題
     * @param PileRequest $request
     * @return Response
     */
    public function submit(PileRequest $request): Response
    {

        $no = $request->get('no');
        $category_id = $request->get('category_id');
        $description = $request->get('description', '');
        $images = $request->get('images');

        if ($images && count($images) > 5) {
            return $this->error();
        }

        $user = $request->user();

        $pile = ChargingPile::query()->where('no', $no)->first();
        if (!$pile) {
            return $this->error();
        }

        $category = FaultCategories::query()->where('id', $category_id)->first();
        if (!$category) {
            return $this->error();
        }

        $data = [
            'pile_id' => $pile['id'],
            'pile_no' => $pile['no'],
            'parking_lot_id' => $pile['parking_lot_id'],
            'user_id' => $user['id'],
            'description' => $description,
            'user_phone' => $user['phone'],
            'user_name' => $user['name'],
            'category_id' => $category_id,
            'category_name' => $category['name'],
        ];

        if ($images && count($images) > 0) {
            $data['images'] = json_encode($images);
        }

        $r = Faults::query()->create($data);
        if (!$r) {
            return $this->error();
        }

//        if (!empty($user['email'])) {
//            $_data = [
//                'user_name' => $user['name'],
//                'content' => $description,
//            ];
//            EmailJob::dispatch($user['email'], $_data, 3);
//        }

        // 提醒商家
        // $user_name = $data['user_name'];
        // $created_at = $data['created_at'];
        // $category_name = $data['category_name'];
        // $content = $data['content'];
        // $user_phone = $data['user_phone'];

       $lot = ParkingLot::query()->with(['region', 'village'])->find($pile['parking_lot_id']);
        $__data = [
            'username' => $user['name'],
            'created_at' => $r['created_at'],
            'category_name' => $category['name'],
            'content' => $description,
            'phone' => $user['phone'],
            'email' => $user['email'],
            'pile_no' => $user['pile_no'],
            'area' => $lot['region']['name']??"".$lot['village']['name'],
            'charging' => $lot['parking_lot_name'],
            'datetime' => date('Y-m-d H:i:s'),
        ];
       // EmailJob::dispatch(env('FAULT_SUBMIT_EMAIL', 'service@evape.com.tw'), $__data, 7);

        Mail::to(env('FAULT_SUBMIT_EMAIL', 'service@evape.com.tw'))->send(new Notice("fault_submit_admin", $__data));

        Mail::to($user['email'])->send(new Notice("fault_submit_user", $__data));

        return $this->success();
    }

    /**
     * 客服問題列表
     * @return Response
     */
    public function index(): Response
    {

        return $this->success([
            'list' => FaultCategories::query()->select('id', 'name')->get()->toArray()
        ]);
    }

    /**
     * 通過充電樁編號獲取充電樁信息
     * @param Request $request
     * @return Response
     */
    public function no(Request $request): Response
    {

        $request->validate([
            'no' => 'required'
        ]);

        $select = [
            'parking_lot_id', 'no'
        ];
        $item = ChargingPile::query()->select($select)->with(['parking' => function($query) {
            $query->select('id', 'parking_lot_name', 'region_id', 'village_id')->with(['region', 'village']);
        }])->where('no', $request->get('no'))->first();

        if ($item) {
            $item['parking_lot_name'] = $item['parking']['parking_lot_name'] ?? '';
            $item['region_name'] = $item['parking']['region']['name'] ?? '';
            $item['village_name'] = $item['parking']['village']['name'] ?? '';

            unset($item['parking']);
            return $this->success([
                'info' => $item
            ]);

        }

        return $this->success([
            'info' => null
        ]);

    }


}
