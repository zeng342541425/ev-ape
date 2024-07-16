<?php

namespace App\Console\Commands;

use App\Workerman\Events;
use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;
use GatewayWorker\Register;
use Illuminate\Console\Command;
use Workerman\Worker;

class WorkerMan extends Command
{
    /**
     * The name and signature of the console command.
     * 命令名稱及簽名
     *
     * @var string
     */
    protected $signature = 'workerman
                            {action : action}
                            {--start=all : start}
                            {--d : daemon mode}';

    /**
     * The console command description.
     * 命令描述
     *
     * @var string
     */
    protected $description = 'Start a Workerman server.';

    /**
     * Create a new command instance.
     * 創建命令
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     * 執行命令
     *
     */
    public function handle()
    {
        global $argv;
        $action = $this->argument('action');

        // 針對 Windows 壹次執行，無法註冊多個協議的特殊處理
        if ($action === 'single') {
            $start = $this->option('start');
            if ($start === 'register') {
                $this->startRegister();
            } elseif ($start === 'gateway') {
                $this->startGateWay();
            } elseif ($start === 'worker') {
                $this->startBusinessWorker();
            }
            Worker::runAll();
            return;
        }


        $options = $this->options();
        $argv[1] = $action;
        $argv[2] = $options['d'] ? '-d' : '';
        $this->start();
    }

    private function start()
    {
        $this->startGateWay();
        $this->startBusinessWorker();
        $this->startRegister();
        Worker::runAll();
    }

    /**
     * gateway進程啟動腳本，包括端口號等設置
     *
     */
    private function startGateWay()
    {
        // 指定websocket協議
        $gateway = new Gateway(config('app.websocket_url'));
        $gateway->name = 'Gateway ' . config('app.name');
        $gateway->count = 1; // CPU核數
        $gateway->lanIp = '127.0.0.1';
        $gateway->startPort = 2300;
        $gateway->pingInterval = 30; // 心跳檢測時間間隔 單位：秒。如果設置為0代表不做任何心跳檢測
        $gateway->pingNotResponseLimit = 0; // 客戶端在pingInterval秒內有pingNotResponseLimit次未回復就斷開連接
        $gateway->pingData = '{"type":"heart"}'; // 發給客戶端的心跳數據
        $gateway->registerAddress = '127.0.0.1:1236';
    }

    /**
     * businessWorker進程啟動腳本
     *
     */
    private function startBusinessWorker()
    {
        $worker = new BusinessWorker();
        $worker->name = 'BusinessWorker ' . config('app.name');
        $worker->count = 3; // CPU核數 1-3倍
        $worker->registerAddress = '127.0.0.1:1236';
        $worker->eventHandler = Events::class;
    }

    /**
     * 註冊服務啟動腳本
     *
     */
    private function startRegister()
    {
        new Register('text://0.0.0.0:1236');
    }
}
