<?php

namespace App\Util\Sms;

interface SmsInterface
{
    public function send(string $phone, string $content,array $options): bool;



}
