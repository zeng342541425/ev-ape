<?php

namespace App\Http\Controllers\Frontend\Booking;

use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\Booking\BookRequest;
use App\Models\Parking\ChargingPile;
use App\Models\Parking\ParkingLot;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;


class BookController extends BaseController
{

    /**
     * 預約停車
     * @param BookRequest $request
     * @return Response
     */
    public function submit(BookRequest $request): Response
    {

        $parking_lot_id = $request->get('parking_lot_id');
        $longitude = $request->get('longitude');
        $latitude = $request->get('latitude');

        $model = ParkingLot::query()->where('status', 1)->where('id', $parking_lot_id);

        $model->with('region:id,name')->with('village:id,name');

        $select = [
            'region_id',
            'village_id',
            'name',
            'address',
            'longitude',
            'latitude',
            'business_hours',
            DB::raw("ST_DISTANCE(ST_GeomFromText('POINT({$longitude} {$latitude})'), POINT(longitude, latitude)) AS distance")
        ];

        $item = $model->select($select)->first();

        if ($item) {

            $charging = ChargingPile::query()
                ->where('parking_lot_id', $parking_lot_id)
                ->first();

            if ($charging) {
                $item['distance'] = intval($item->distance * 111195);
                $item['region_name'] = $item->region->name;
                $item['village_name'] = $item->village->name;
                $item['no'] = $charging->no;
                $item['parking_lot_id'] = $parking_lot_id;

                unset($item->region, $item->village, $item->region_id, $item->village_id);

                return $this->success(['detail' => $item]);
            }

        }

        return $this->error(__('message.charging_pile_not_found'));

    }

}
