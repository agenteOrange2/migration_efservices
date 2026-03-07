<?php

namespace App\Mail;

use App\Models\UserCarrier;
use App\Models\User;
use App\Models\Carrier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewCarrierAdminNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $carrier;
    public $eventType;
    public $step;
    public $data;
    public $userCarrier; // Mantener compatibilidad hacia atrás

    /**
     * Create a new message instance.
     */
    public function __construct(
        $userOrUserCarrier,
        ?Carrier $carrier = null,
        ?string $eventType = null,
        ?string $step = null,
        array $data = []
    ) {
        // Compatibilidad hacia atrás con UserCarrier
        if ($userOrUserCarrier instanceof UserCarrier) {
            $this->userCarrier = $userOrUserCarrier;
            $this->user = $userOrUserCarrier->user;
            $this->carrier = $userOrUserCarrier->carrier;
            $this->eventType = 'legacy';
            $this->step = null;
            $this->data = [];
        } else {
            // Nuevo sistema con User - cargar relaciones necesarias
            $this->user = $userOrUserCarrier;
            
            // Cargar carrierDetails si no está cargado
            if ($this->user && !$this->user->relationLoaded('carrierDetails')) {
                $this->user->load('carrierDetails');
            }
            
            // Cargar membership del carrier si existe
            $this->carrier = $carrier;
            if ($this->carrier && !$this->carrier->relationLoaded('membership')) {
                $this->carrier->load('membership');
            }
            
            $this->eventType = $eventType ?? 'unknown';
            $this->step = $step;
            $this->data = $data;
            $this->userCarrier = null;
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->getSubjectByEventType();
        
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get subject based on event type
     */
    private function getSubjectByEventType(): string
    {
        $carrierName = $this->carrier?->name ?? ($this->user?->name ?? 'Nuevo Carrier');
        
        // Mapeo de nombres de pasos para el subject
        $stepNames = [
            'step1' => 'Información Básica',
            'step2' => 'Información de Empresa',
            'step3' => 'Membresía',
            'step4' => 'Info. Bancaria',
        ];
        $stepDisplayName = $stepNames[$this->step] ?? $this->step;
        
        return match($this->eventType) {
            'step_completed' => "📋 Paso Completado: {$stepDisplayName} - {$carrierName}",
            'registration_completed' => "🎉 Nuevo Carrier Registrado: {$carrierName}",
            'legacy' => '📢 Notificación de Carrier',
            default => '📢 Notificación del Sistema de Carriers'
        };
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new_carrier_admin_notification',
            with: [
                'user' => $this->user,
                'carrier' => $this->carrier,
                'eventType' => $this->eventType,
                'step' => $this->step,
                'data' => $this->data,
                'userCarrier' => $this->userCarrier, // Compatibilidad hacia atrás
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
