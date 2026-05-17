<?php

namespace App\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\User;
use App\Models\Device_tokens;
use App\Models\Notificationn;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationToFollowers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $title, $body, $adminId, $adminName, $followerIds;

    public function __construct($title, $body, $adminId, $adminName, $followerIds)
    {
        $this->title = $title;
        $this->body = $body;
        $this->adminId = $adminId;
        $this->adminName = $adminName;
        $this->followerIds = $followerIds;
    }

    public function handle()
    {
        $tokens = Device_tokens::whereIn('user_id', $this->followerIds)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return; // فقط تجاهل
        }

        $body = "{$this->body} - مرسل الإشعار: {$this->adminName}";

        $message = \Kreait\Firebase\Messaging\CloudMessage::new()
            ->withNotification(
                \Kreait\Firebase\Messaging\Notification::create(
                    $this->title,
                    $body
                )
            );

        foreach (array_chunk($tokens, 500) as $chunk) {
            \Kreait\Laravel\Firebase\Facades\Firebase::messaging()
                ->sendMulticast($message, $chunk);
        }

        foreach (array_chunk($this->followerIds, 1000) as $chunk) {
            $notifications = [];

            foreach ($chunk as $followerId) {
                $notifications[] = [
                    'title' => $this->title,
                    'body' => $body,
                    'user_id' => $followerId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Notificationn::insert($notifications);
        }

        User::whereIn('id', $this->followerIds)
            ->increment('numberOfNotifications');
    }
}
