<?php

namespace App\Console\Commands;

use App\Jobs\NoticePushJob;
use App\Models\Common\FirebasePush;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FirebasePushAtTime extends Command
{
    /**
     * 公告推播預約發送
     *
     * @var string
     */
    protected $signature = 'FirebasePushAtTime';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '公告推播預約發送';

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

        Log::info('公告推播預約發送-開始');

        // 點數活動時間過期自動下架
        $current_datetime = date('Y-m-d H:i:s');
        $firebaseMessage = FirebasePush::query()->where('type', 2)
            ->where('status', 0)
            ->where('send_time', '<=', $current_datetime)->first();

        if ($firebaseMessage) {
            FirebasePush::query()->where('id', $firebaseMessage['id'])->update([
                'status' => 1
            ]);
            NoticePushJob::dispatch($firebaseMessage);
        }

        Log::info('公告推播預約發送-結束');

    }
}
