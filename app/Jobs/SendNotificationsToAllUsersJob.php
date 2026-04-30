<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\DeviceToken;
use App\Models\Notification;

class SendNotificationsToAllUsersJob implements ShouldQueue
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
        $tokens = DeviceToken::join('users', 'device_tokens.user_id', '=', 'users.id')
            ->where('users.role', 'user')
            ->pluck('device_tokens.token')
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

        foreach (array_chunk($tokens, 500) as $chunk) {
            \Kreait\Laravel\Firebase\Facades\Firebase::messaging()
                ->sendMulticast($message, $chunk);
        }

        Notification::create([
            'title' => $this->title,
            'body' => $this->body,
            'is_global' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        User::whereIn('id', $this->ids)
            ->increment('numberOfNotifications');
    }
}
