<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;

class NotificationService
{
    public function notify(User $user, string $type, string $title, ?string $body = null, ?array $data = null): AppNotification
    {
        return AppNotification::create([
            'user_id' => $user->id,
            'channel' => 'push',
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sent_at' => now(),
        ]);
    }
}
