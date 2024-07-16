<?php

namespace App\Console\Commands;

use App\Models\Common\Banner;
use App\Models\Common\DiningBooking;
use App\Models\Frontend\User\User;
use App\Models\Order\InvoiceRequest;
use App\Models\Order\Order;
use App\Services\Common\InvoiceService;
use App\Services\Common\PaymentService;
use App\Services\Common\TapPayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ResetInvoice extends Command
{
    /**
     * 重新開設發票
     *
     * @var string
     */
    protected $signature = 'ResetInvoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '重新開設發票';

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

        Log::info('重新開設發票-開始');

        // 把前一天開票失敗的找出來
        $start_yesterday = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $end_yesterday = date('Y-m-d 23:59:59', strtotime('-1 day'));
        $invoice_service = new InvoiceService();
        InvoiceRequest::query()->where('yeah', 0)
            ->where('updated_at', '>=', $start_yesterday)
            ->where('updated_at', '<=', $end_yesterday)
            ->chunkById(200, function ($order) use ( $invoice_service) {

            foreach($order as $v) {
                $request_id = $v['request_id'];

                $request_data = json_decode($v['request_data'], true);
                $res = $invoice_service->invoiceRequests($request_data, $request_id);

                if ($res) {

                    if (isset($res['status']) && $res['status'] == 0) {

                        // 成功
                        InvoiceRequest::query()->where('request_id', $v['request_id'])->update([
                            'rec_trade_id' => $res['rec_invoice_id'] ?? '',
                            'content' => json_encode($res),
                            'yeah' => 1
                        ]);

                        // 更新餐廳預約
                        if ($v['type'] == 2) {
                            !empty($res['invoice_number']) && DiningBooking::query()->where('id', $v['order_id'])->update([
                                'invoice_number' => $res['invoice_number']
                            ]);
                        }

                        // 更新充電
                        if ($v['type'] == 1) {
                            !empty($res['invoice_number']) && Order::query()->where('id', $v['order_id'])->update([
                                'invoice_number' => $res['invoice_number']
                            ]);
                        }
                    } else {
                        if (isset($res['rec_invoice_id']) && !empty($res['rec_invoice_id'])) {
                            InvoiceRequest::query()->where('request_id', $v['request_id'])->update([
                                'rec_trade_id' => $res['rec_invoice_id'],
                                'content' => json_encode($res),
                            ]);
                        }
                    }

                }


            }
        });

        Log::info('重新開設發票-結束');

    }
}
