<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Device_tokens;
use App\Models\Notificationn;
class SendNotificationsBySuperAdmin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $title, $body, $ids;

    public function __construct($title, $body, $ids)
    {
        $this->title = $title;
        $this->body = $body;
        $this->ids = $ids;
    }

    public function handle()
    {
        $tokens = Device_Tokens::whereIn('user_id', $this->ids)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return;
        }

        $message = \Kreait\Firebase\Messaging\CloudMessage::new()
            ->withNotification(
                \Kreait\Firebase\Messaging\Notification::create(
                    $this->title,
                    $this->body
                )
            );

        // إرسال الإشعارات
        foreach (array_chunk($tokens, 500) as $chunk) {
            \Kreait\Laravel\Firebase\Facades\Firebase::messaging()
                ->sendMulticast($message, $chunk);
        }

        // تخزين الإشعارات
        foreach (array_chunk($this->ids, 1000) as $chunk) {
            $notifications = [];

            foreach ($chunk as $id) {
                $notifications[] = [
                    'title' => $this->title,
                    'body' => $this->body,
                    'user_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Notificationn::insert($notifications);
        }

        // تحديث العداد
        User::whereIn('id', $this->ids)
            ->increment('numberOfNotifications');
    }
}