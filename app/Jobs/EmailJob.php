<?php

namespace App\Jobs;

use App\Services\Common\EmailCodeService;
use Illuminate\Support\Facades\Log;

/**
 * 固定推播隊列
 */
class EmailJob extends BaseJob
{

    public int $tries = 3;

    private mixed $email = '';
    private mixed $type = 1;
    private array $data;

    public string $name = "信件隊列";
    public string $desc = "信件隊列";

    public function __construct($email = '', $data = [], $type = 1)
    {

        parent::__construct();

        $this->onQueue('email_job');

        $this->email = $email;
        $this->type = $type;
        $this->data = $data;

    }

    public function handle()
    {

        if (!empty($this->email)) {
            Log::info('信件隊列-開始', ['data' => $this->data, 'type' => $this->type]);

            (new EmailCodeService($this->email, $this->data, $this->type))->send();

            Log::info('信件隊列-結束', ['data' => $this->data, 'type' => $this->type]);
        }

    }
}
