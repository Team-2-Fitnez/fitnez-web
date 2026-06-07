<?php

namespace App\Listeners;

use App\Events\NewChatMessage;
use App\Events\NewNotification;

class BroadcastViaSocketio
{
    public function handleNewChatMessage(NewChatMessage $event): void
    {
        $event->broadcast();
    }

    public function handleNewNotification(NewNotification $event): void
    {
        $event->broadcast();
    }

    public function subscribe(): array
    {
        return [
            NewChatMessage::class => 'handleNewChatMessage',
            NewNotification::class => 'handleNewNotification',
        ];
    }
}
