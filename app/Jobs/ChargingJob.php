<?php

namespace App\Jobs;

use App\Services\Common\PaymentService;
use App\Services\Common\PileService;
use Exception;
use Illuminate\Support\Facades\Log;

class ChargingJob extends BaseJob
{

    public int $tries = 3;

    private int $order_id;
    private string $serial_number;

    public string $name = "充電";
    public string $desc = "用於和充電樁請求供電";

    public function __construct(string $serial_number = '', int $order_id = 0)
    {

        parent::__construct();

        $this->onQueue('charging');

        $this->order_id = $order_id;
        $this->serial_number = $serial_number;
    }

    /**
     * @throws Exception
     */
    public function handle()
    {

        $code = (new PileService())->charging($this->serial_number, $this->order_id);
        if ($code != 200) {
            // 成功
            throw new Exception('start-charging請求失敗，'.$this->order_id, $code);
        }

    }
}
