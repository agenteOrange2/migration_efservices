<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Driver\Driver;

class InspectionRecordedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $inspectionId;
    protected Driver $driver;
    protected string $inspectionType;
    protected bool $hasViolations;
    protected ?int $violationCount;

    public function __construct(int $inspectionId, Driver $driver, string $inspectionType, bool $hasViolations = false, ?int $violationCount = null)
    {
        $this->inspectionId = $inspectionId;
        $this->driver = $driver;
        $this->inspectionType = $inspectionType;
        $this->hasViolations = $hasViolations;
        $this->violationCount = $violationCount;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->hasViolations 
            ? 'URGENT: Inspection with Violations - ' . $this->driver->full_name
            : 'Inspection Recorded - ' . $this->driver->full_name;
            
        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello,')
            ->line('An inspection has been recorded.')
            ->line('**Driver:** ' . $this->driver->full_name)
            ->line('**Inspection Type:** ' . $this->inspectionType);
            
        if ($this->hasViolations) {
            $mail->line('**Violations Found:** ' . ($this->violationCount ?? 'Yes'));
        }
        
        return $mail
            ->action('View Inspection', route('carrier.inspections.show', $this->inspectionId))
            ->line($this->hasViolations ? 'Please review the violations immediately.' : 'No violations were found.');
    }

    public function toArray(object $notifiable): array
    {
        $message = $this->driver->full_name . ' recorded a ' . $this->inspectionType . ' inspection';
        if ($this->hasViolations) {
            $message .= ' with ' . ($this->violationCount ?? 'some') . ' violation(s)';
        }
        
        return [
            'title' => $this->hasViolations ? 'Inspection with Violations' : 'Inspection Recorded',
            'message' => $message,
            'type' => 'inspection_recorded',
            'category' => 'safety',
            'icon' => $this->hasViolations ? 'AlertTriangle' : 'ClipboardCheck',
            'urgent' => $this->hasViolations,
            'inspection_id' => $this->inspectionId,
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'inspection_type' => $this->inspectionType,
            'has_violations' => $this->hasViolations,
            'violation_count' => $this->violationCount,
            'url' => route('carrier.inspections.show', $this->inspectionId),
        ];
    }
}
