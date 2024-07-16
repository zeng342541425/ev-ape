<?php

namespace App\Jobs;


use App\Workerman\GateWay;
use Illuminate\Support\Facades\Log;

class Wrokerman extends BaseJob
{

    public int $tries = 3;

    private string $clientId;
    private array $data;

    public string $name = "充電訊息推送";
    public string $desc = "充電相關的訊息推送到app進行展示";

    public function __construct(string $clientId, array $data = [])
    {

        parent::__construct();

        $this->onQueue('charging_socket');

        $this->clientId = $clientId;
        $this->data = $data;
    }

    public function handle()
    {

        try {
            GateWay::sendResponseToClient($this->clientId, \response()->json($this->data));
            Log::channel('workerman')->info('job', [
                'clientId' => $this->clientId,
                'data'     => $this->data,
            ]);
        } catch (\Throwable $e) {
            Log::channel('workerman')->error('job', [
                'msg'  => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }


    }
}
