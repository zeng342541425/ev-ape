<?php

namespace App\Console\Commands;

use App\Models\Common\Banner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BannerEndUpdate extends Command
{
    /**
     * 自動下架組合商品
     *
     * @var string
     */
    protected $signature = 'BannerEndUpdate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '首頁輪播圖自動下架';

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

        Log::info('首頁輪播圖自動下架-開始');

        // 點數活動時間過期自動下架
        $current_datetime = date('Y-m-d H:i:s');
        Banner::query()
            ->where('ending_time', '<', $current_datetime)
            ->where('status', 1)
            ->update(['status' => 0]);

        Log::info('首頁輪播圖自動下架-結束');

    }
}
