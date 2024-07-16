<?php

namespace App\Jobs;

use App\Models\Backend\User\User;
use App\Models\Common\DiningBooking;
use App\Models\Order\InvoiceRequest;
use App\Services\Common\Common;
use App\Services\Common\InvoiceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class InvoiceJob extends BaseJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public int $tries = 3;

    public string $name = "發票隊列";
    public string $desc = "發票隊列";
    private int $id;
    private string $action;
    private int $type;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id,$action,$type)
    {
        parent::__construct();

        $this->onQueue('order_payment');
        //
        $this->id = $id;
        $this->action = $action;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $this->{$this->action}();
    }

    protected function send()
    {
        switch ($this->type){
            case 1: //充電樁
                $this->sendOrder();
                break;
            case 2: //餐旅
                $this->sendDining();
                break;
        }

    }

    protected function sendDining()
    {
        $data = DiningBooking::query()->find($this->id);

        if (!$data) return false;

        $user = User::query()->find($data['user_id']);

        $buyer_email = $user['email'] ?? '';
        $in_info = json_decode($data['invoice_info'], true);
        $invoice_info = $in_info['invoice_info'];

        $buyer_name = $invoice_info['title'];
        $buyer_identifier = $invoice_info['tax_id'];
        $total_amount = $data['number'] * $data['charging'];
        $order_date = str_replace('-', '', substr($data['created_at'], 0, 10));

        $order_number = Common::generateInvoiceNo();

        $request_id = Str::uuid()->toString();

        $request_data = [
            'order_number' => $order_number,
            'order_date' => $order_date,
            'buyer_name' => $buyer_name,
            'buyer_email' => $buyer_email,
            'currency' => 'TWD',
            'total_amount' => intval($total_amount),
            'details' => [
                [
                    'sequence_id' => '002',
                    'sub_amount' => intval($total_amount),
                    'unit_price' => intval($data['charging']),
                    'quantity' => intval($data['number']),
                    'description' => '【受託代銷】餐旅報到',
                    'tax_type' => 1,
                ]
            ],
            'notify_url' => route('invoice_notify')
        ];

        if (!empty($buyer_identifier)) {
            $request_data['buyer_identifier'] = $buyer_identifier;
        }

        $in_request = InvoiceRequest::query()->where('type', 2)->where('order_id', $data['id'])->first();

        if ($in_request) {
            // $order_number = $in_request['order_number'];
            // $request_id = $in_request['request_id'];
            // $request_data = json_encode($in_request['requests_data']);
            InvoiceRequest::query()->where('type', 2)->where('order_id', $data['id'])->update([
                'order_number' => $order_number,
                'request_id' => $request_id,
                'requests_data' => json_encode($request_data),
            ]);

        } else {

            InvoiceRequest::query()->create([
                'user_id' => $user['id'],
                'order_id' => $data['id'],
                'order_number' => $order_number,
                'api' => 'einvoice/issue',
                'type' => 2,
                'request_id' => $request_id,
                // 'rec_trade_id' => $res['rec_invoice_id'] ?? '',
                // 'content' => json_encode($res),
                'requests_data' => json_encode($request_data),
            ]);

        }

        // $tapPayService = new TapPayService();
        $res = InvoiceService::invoiceRequests($request_data, $request_id);

        if ($res) {

            if (isset($res['status']) && $res['status'] == 0) {
                InvoiceRequest::query()->where('order_id', $data['id'])->where('type', 2)->update([
                    'rec_trade_id' => $res['rec_invoice_id'] ?? '',
                    'content' => json_encode($res),
                    'yeah' => 1
                ]);

                $data->update(['invoice_number'=>$res['invoice_number']]);

                return $res;

            } else {

                if (isset($res['rec_invoice_id']) && !empty($res['rec_invoice_id'])) {
                    InvoiceRequest::query()->where('order_id', $data['id'])->where('type', 2)->update([
                        'rec_trade_id' => $res['rec_invoice_id'],
                        'content' => json_encode($res),
                        'yeah' => 0
                    ]);
                }

            }


        }

        return false;
    }

    protected function sendOrder()
    {

    }
}
