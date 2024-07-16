<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class RoleChange extends Notification implements ShouldQueue
{
    use Queueable;

    public  $data;

    /**
     * @param $data
     */
    public function __construct( $data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * 確定哪些隊列應該被通知頻道使用。
     *
     * @return array
     */
    public function viaQueues(): array
    {
        return [
            'database' => 'notification',
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable): array
    {
        return [
            'form' => 'system',
            'message' => __('message.role.change'),
            'data' => $this->data
        ];
    }
}
