<?php

namespace App\Http\Controllers\Frontend\Parking;

use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\Parking\Map\FavoriteRequest;
use App\Http\Requests\Frontend\Parking\Map\MapRequest;
use App\Models\Parking\ChargingPile;
use App\Models\Parking\Favorite;
use App\Models\Parking\ParkingLot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;


class FavoriteController extends BaseController
{

    /**
     * 收藏停車場
     * @param FavoriteRequest $request
     * @return Response
     */
    public function submit(FavoriteRequest $request): Response
    {

        $parking_lot_id = $request->get('parking_lot_id');

        $item = ParkingLot::query()->select('id')->where('id', $parking_lot_id)->first();

        if ($item) {
            $user_id = Auth::id();
            $e = Favorite::query()->where('user_id', $user_id)->where('parking_lot_id', $parking_lot_id)->exists();

            if (!$e) {
                $create_data = [
                    'parking_lot_id' => $parking_lot_id,
                    'user_id' => $user_id,
                ];

                $r = Favorite::query()->create($create_data);
                if ($r) {
                    return $this->success([
                        'type' => 'added',
                    ]);
                }
            } else {
                $r = Favorite::query()->where('user_id', $user_id)->where('parking_lot_id', $parking_lot_id)->delete();
                if ($r) {
                    return $this->success([
                        'type' => 'canceled',
                    ]);
                }
            }

        }

        return $this->error();
    }


}
