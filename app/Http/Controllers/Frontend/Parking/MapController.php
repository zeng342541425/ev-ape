<?php

namespace App\Http\Controllers\Frontend\Parking;

use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\Parking\Map\MapRequest;
use App\Models\Order\Order;
use App\Models\Parking\ChargingPile;
use App\Models\Parking\Favorite;
use App\Models\Parking\ParkingLot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;


class MapController extends BaseController
{

    /**
     * 地圖搜尋
     * @param MapRequest $request
     * @return Response
     */
    public function index(MapRequest $request): Response
    {

        $longitude = $request->get('longitude', config('evape.default_longitude'));
        $latitude = $request->get('latitude', config('evape.default_latitude'));
        $favorite = $request->get('favorite', 0);
        $region_id = $request->get('region_id', 0);
        $village_id = $request->get('village_id', 0);
        $specification_id = $request->get('specification_id', 0);
        $power_id = $request->get('power_id', 0);

        $user = $request->user();

        if ($user) {
            $model = ParkingLot::query()->where('status', 1)->where('audit_status', 1)->with(['favorite_with' => function($query) use($user){
                $query->where('user_id', $user['id']);
            }]);
        } else {
            $model = ParkingLot::query()->where('status', 1)->where('audit_status', 1);
        }

        // 功率規格篩選
        if ($power_id > 0 || $specification_id > 0) {

            $pile_ids = [0];
            $charging_model = ChargingPile::query()->select('parking_lot_id');

            if ($power_id > 0) {
                $charging_model->where('power_id', $power_id);
            }

            if ($specification_id > 0) {
                $charging_model->where('specification_id', $specification_id);
            }

            $pile_list = $charging_model->get()->toArray();
            if ($pile_list) {
                $pile_ids = array_column($pile_list, 'parking_lot_id');
            }

            $model->whereIn('id', $pile_ids);
        }

        // 最愛篩選
        if ($favorite > 0) {

            $user_id = $user['id'];
            $favorite_list = Favorite::query()->select('parking_lot_id')->where('user_id', $user_id)->get()->toArray();
            $favorite_ids = [0];
            if ($favorite_list) {
                $favorite_ids = array_column($favorite_list, 'parking_lot_id');
            }

            $model->whereIn('id', $favorite_ids);

        }

        // 縣市篩選
        if ($region_id > 0) {
            $model->where('region_id', $region_id);
        }

        // 鄉鎮區篩選
        if ($village_id > 0) {
            $model->where('village_id', $village_id);
        }

        $model->with('region:id,name')->with('village:id,name');

        $select = [
            'id',
            'region_id',
            'village_id',
            'parking_lot_name',
            'address',
            'longitude',
            'latitude',
            'business_hours',
            'charging_range',
            'toll_range',
            // 'preferential_range',
            'parking_fee',
            'images',
            DB::raw("ST_DISTANCE(ST_GeomFromText('POINT({$longitude} {$latitude})'), POINT(longitude, latitude))*111195 AS distance"),
            'power_values',
            'specification_values',
            'notes',
        ];

        if ($region_id == 0 && $favorite == 0) {
            // 半徑，需要調整，暫定10km
           // $model->having('distance', '<=', 10000);
        }


        $list = $model->select($select)->orderBy('distance')->get()->toArray();

        if ($list) {
            $parking_ids = array_column($list, 'id');
            $select = [
                'parking_lot_id',
                // DB::raw("count(id) as total"),audit_status
                DB::raw("SUM(IF(`audit_status`=1, 1, 0)) AS total"),
                DB::raw("SUM(IF(`audit_status`=1 AND `status`=0, 1, 0)) AS free_time_number"),
                // DB::raw("SUM(IF(`status`=1, 1, 0)) AS busy_number"),
            ];
            $charging_list = ChargingPile::query()->select($select)
                ->whereIn('parking_lot_id', $parking_ids)
                ->groupBy('parking_lot_id')
                ->get()
                ->toArray();

            $charging_map_list = [];
            if ($charging_list) {
                foreach ($charging_list as $v) {
                    $charging_map_list[$v['parking_lot_id']] = $v;
                }
            }

            $star_list = $this->getStar($parking_ids);

            foreach ($list as $k => $v) {
                $total = $charging_map_list[$v['id']]['total'] ?? 0;
                $free_time_number = $charging_map_list[$v['id']]['free_time_number'] ?? 0;
                $list[$k]['distance'] = intval($v['distance']);
                $list[$k]['total'] = (int) $total;
                $list[$k]['free_time_number'] = (int) $free_time_number;
                $list[$k]['region_name'] = $v['region']['name'];
                $list[$k]['village_name'] = $v['village']['name'];
                $list[$k]['parking_lot_id'] = $v['id'];

                $list[$k]['favorite'] = 0;
                if (!empty($v['favorite_with'])) {
                    $list[$k]['favorite'] = 1;
                }
                // $list[$k]['busy_number'] = (int) $charging_map_list[$v['id']]['busy_number'];

                $list[$k]['images'] = json_decode($v['images'], true);

                $list[$k]['star'] = $star_list[$v['id']] ?? '5.0';

                unset($list[$k]['region'], $list[$k]['village'], $list[$k]['region_id'], $list[$k]['village_id'],
                    $list[$k]['id'], $list[$k]['favorite_with']);

            }

        }

        return $this->success(['list' => $list]);
    }


    /**
     * 地圖搜尋
     * @param MapRequest $request
     * @return Response
     */
    public function list(MapRequest $request): Response
    {

        $search_word = $request->get('search_word', '');

        $user = $request->user();
        $model = ParkingLot::query()->where('status', 1)->where('audit_status', 1);

        if (!empty($search_word)) {
            $model->where(function($q) use($search_word) {
                $q->where('parking_lot_name', 'like', "%$search_word%");
                // $q->orWhere('address', 'like', "%$search_word%");
            });
        }

        $model->with('region:id,name')->with('village:id,name');

        $select = [
            // 'id',
            'region_id',
            'village_id',
            'parking_lot_name',
            'address',
            'business_hours',
            'charging_range',
            'toll_range',
            // 'preferential_range',
            'parking_fee',
            'images',
            'power_values',
            'specification_values',
            'latitude',
            'longitude',
            'notes',
        ];

        $list = $model->select($select)->get()->toArray();

        if ($list) {

            foreach ($list as $k => $v) {

                $list[$k]['region_name'] = $v['region']['name'];
                $list[$k]['village_name'] = $v['village']['name'];

                $list[$k]['images'] = json_decode($v['images'], true);

                unset($list[$k]['region'], $list[$k]['village'], $list[$k]['region_id'], $list[$k]['village_id'],
                    $list[$k]['id']);

            }

        }

        return $this->success(['list' => $list]);
    }

    protected function getStar($parking_ids): array
    {

        $select = [
            'parking_lot_id',
            DB::raw("count(id) as total"),
            DB::raw("SUM(star) AS total_start"),
        ];
        $start_list = Order::query()->select($select)->whereIn('parking_lot_id', $parking_ids)
            ->where('star', '>', 0)->groupBy('parking_lot_id')->get()->toArray();
        $start_map_list = [];
        if ($start_list) {
            foreach ($start_list as $v) {
                $start_total_number = $v['total'];
                $total_start = $v['total_start'];
                $s = strval(round($total_start/$start_total_number, 1));
                $start_map_list[$v['parking_lot_id']] = strlen($s) == 1 ? $s . '.0' : $s;
            }
        }

        return $start_map_list;

    }

}
