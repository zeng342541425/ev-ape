<?php

namespace App\Exports;


use App\Models\Backend\User\User;
use App\Models\Common\Appointment;
use App\Models\Order\Order;
use App\Models\User\Invoice;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class UserDatumExport extends \PhpOffice\PhpSpreadsheet\Cell\StringValueBinder implements FromView,ShouldAutoSize
{



    public  $user_info;
    public  $user_invoices;
    public  $user_orders;
    public  $user_appointments;

    public function __construct($user_id)
    {
        $this->user_info = User::query()->with('brand:id,brand_name')->find($user_id);
        $this->user_invoices = Invoice::query()->where('user_id',$user_id)->get();
        $this-> user_orders = Order::query()->with(['parking' => function($q) {
            $q->with(['region', 'village']);
        }])->where('user_id',$user_id)->orderByDesc('id')->get();
        $this-> user_appointments = Appointment::query()->with(['parking' => function($q) {
            $q->select('id', 'parking_lot_name', 'region_id', 'village_id')->with(['region', 'village']);
        }])->where('user_id',$user_id)->orderByDesc('id')->get();
    }

    public function view(): View
    {
        $appointment_status_map = [
            0 => '否',
            1 => '是',
        ];
        $status_map = [
            1 => '否',
            2 => '是',
        ];
        return view('exports.user_datum', [
            'user_info' => $this->user_info,
            'user_invoices' => $this->user_invoices,
            'user_orders' => $this->user_orders,
            'user_appointments' => $this->user_appointments,
            'appointment_status_map' => $appointment_status_map,
            'status_map' => $status_map,
        ]);
    }
}
