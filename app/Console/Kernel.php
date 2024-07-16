<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        // token過期自動刪除
        $schedule->command('sanctum:prune-expired', ['--hours=1'])->hourly();

        // banner自動下架
        $schedule->command('BannerEndUpdate')->everyFiveMinutes();
        $schedule->command('DiningCancel')->everyTenMinutes();

        // 公告推播預約發送
        $schedule->command('FirebasePushAtTime')->everyMinute();

        // 預約充電未到場進行取消
        $schedule->command('AppointmentCancel')->everyMinute();

        // 預約充電時間30分鐘前
        $schedule->command('AppointmentTips')->everyMinute();

        // 充電扣款失敗重新發起扣款
        $schedule->command('ResetPayment')->dailyAt('00:27');
        // $schedule->command('ResetPayment')->dailyAt('01:27');
        $schedule->command('ResetDiningPayment')->dailyAt('00:37');
        // $schedule->command('ResetDiningPayment')->dailyAt('01:37');

        // 重新開設發票
        $schedule->command('ResetInvoice')->dailyAt('01:00');

        // 三天後享用餐旅的服務提醒
        $schedule->command('DiningFreeTips')->dailyAt('09:01');
        $schedule->command('DiningYesterdayTips')->dailyAt('09:06');

        // 更新最新消息的發佈狀態
        $schedule->command('MessagePublishAtTime')->everyMinute();

        // 停車充電、餐廳飯店沒有補繳金額 通知
        $schedule->command('OrderSupplementaryPaymentTips')->dailyAt('10:37');
        $schedule->command('BookingSupplementaryPaymentTips')->dailyAt('10:07');

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
