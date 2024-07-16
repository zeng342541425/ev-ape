<?php

namespace App\Console\Commands;

use App\Jobs\MessageFirebaseJob;
use App\Models\Common\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MessagePublishAtTime extends Command
{
    /**
     * 最新消息預約發佈
     *
     * @var string
     */
    protected $signature = 'MessagePublishAtTime';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '最新消息預約發佈';

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

        Log::info('最新消息預約發佈-開始');

        // 點數活動時間過期自動下架
        $current_datetime = date('Y-m-d H:i:s');
       $list = Message::query()
            ->where('status', 0)
            ->where('published_at', '<=', $current_datetime)->get();
           // ->update(['status' => 1]);

        foreach ($list as $value){
            $value->update(['status' => 1]);

            if ($value['type'] !== 2 && $value['status'] == 1){
                MessageFirebaseJob::dispatch($value);
            }
        }

        Log::info('最新消息預約發佈-結束');

    }
}
