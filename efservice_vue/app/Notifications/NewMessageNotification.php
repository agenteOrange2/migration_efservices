<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $senderName;
    protected string $messagePreview;
    protected bool $isUrgent;
    protected ?int $conversationId;

    public function __construct(string $senderName, string $messagePreview, bool $isUrgent = false, ?int $conversationId = null)
    {
        $this->senderName = $senderName;
        $this->messagePreview = $messagePreview;
        $this->isUrgent = $isUrgent;
        $this->conversationId = $conversationId;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $title = $this->isUrgent ? 'Urgent Message from ' . $this->senderName : 'New Message from ' . $this->senderName;
        
        return [
            'title' => $title,
            'message' => $this->messagePreview,
            'type' => 'new_message',
            'category' => 'messages',
            'icon' => $this->isUrgent ? 'AlertCircle' : 'Mail',
            'urgent' => $this->isUrgent,
            'sender_name' => $this->senderName,
            'conversation_id' => $this->conversationId,
        ];
    }
}
