<?php

namespace App\Console\Commands;

use App\Jobs\RegularPushJob;
use App\Models\Common\Appointment;
use App\Models\Common\Banner;
use App\Models\Common\DiningBooking;
use App\Models\Frontend\User\User;
use App\Services\Common\InvoiceService;
use App\Services\Common\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentCancel extends Command
{
    /**
     * 預約充電未到場
     *
     * @var string
     */
    protected $signature = 'AppointmentCancel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '預約充電未到場';

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

        Log::info('預約充電未到場-開始');

        // 點數活動時間過期自動下架
        $current_datetime = date('Y-m-d H:i:s');
        Appointment::query()->whereIn('status', [0])
            ->where('expired_at', '<', $current_datetime)
            ->chunkById(100, function ($users) {

                $user_ids = [];
                $ids = [];
                foreach($users as $v) {
                    $user_ids[] = $v->user_id;
                    $ids[] = $v->id;
                }

                Appointment::query()->whereIn('id', $ids)->update([
                    'status' => 3
                ]);

                // 推播
                $key = 'appointment_not_starting';
                RegularPushJob::dispatch($user_ids, $key);

        });

        Log::info('預約充電未到場-結束');

    }
}
