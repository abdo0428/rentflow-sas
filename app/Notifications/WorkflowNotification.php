<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class WorkflowNotification extends Notification
{
    /** @param array<string, string|int> $parameters */
    public function __construct(
        public string $message,
        public string $routeName,
        public int $recordId,
        public array $parameters = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['message' => $this->message, 'route' => $this->routeName, 'record_id' => $this->recordId, 'parameters' => $this->parameters];
    }
}
