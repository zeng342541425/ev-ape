<?php

namespace App\Console\Commands;

use App\Jobs\RegularPushJob;
use App\Models\Common\Appointment;
use App\Models\Common\Banner;
use App\Models\Common\DiningBooking;
use App\Models\Frontend\User\User;
use App\Models\Parking\ChargingPile;
use App\Services\Common\InvoiceService;
use App\Services\Common\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentTips extends Command
{
    /**
     * 預約充電時間30分鐘前
     *
     * @var string
     */
    protected $signature = 'AppointmentTips';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '預約充電時間30分鐘前';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {

        Log::info('預約充電時間30分鐘前-開始');

        // 點數活動時間過期自動下架
        $starting_time = time() + 30 * 60;
        $starting_datetime = date('Y-m-d H:i:00', $starting_time);

        $ending_time = time() + 31 * 60;
        $ending_datetime = date('Y-m-d H:i:00', $ending_time);

        Appointment::query()->where('status', 0)
            ->where('appointment_at', '>=', $starting_datetime)
            ->where('appointment_at', '<', $ending_datetime)
            ->chunkById(200, function ($users) {

                $p_ids = $users->pluck('pile_id')->toArray();

                $list = ChargingPile::query()->whereIn('id',$p_ids)->whereIn('status',[1,2])->pluck('id')->toArray();
                if (empty($list)){
                    return;
                }
                $user_ids = [];
                foreach($users as $v) {
                    if (!in_array($v['pile_id'],$list)) continue;
                    $user_ids[] = $v->user_id;
                }

                if (!empty($user_ids)){
                    // 推播
                    $key = 'appointment_30';
                    RegularPushJob::dispatch($user_ids, $key);
                }

        });

        Log::info('預約充電時間30分鐘前-結束');

    }
}
