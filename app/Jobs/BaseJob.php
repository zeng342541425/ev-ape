<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class BaseJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $name = "";
    public string $desc = "";

    //任務失敗的處理過程
    public function failed(Throwable $exception)
    {
        Log::error("隊列失敗：".$exception->getMessage(), $exception->getTrace());
    }

    public function release($delay = 0)
    {
        Log::info("完成了：" . get_called_class());
    }

    public function fail($exception = null)
    {
        Log::info("失败了：" . get_called_class());
    }

    /**
     * @throws Exception
     */
    public function __construct()
    {
        Log::info('begin ' . get_called_class());
        // echo 'begin ' . get_called_class();

        if (strlen($this->name) == 0) {
            throw new Exception('attribute "name" not setting');
        }

        if (strlen($this->desc) == 0) {
            throw new Exception('attribute "desc" not setting');
        }

    }

}
