<?php

namespace App\Notifications;

use App\Enums\TravelRequestStatus;
use App\Models\TravelRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TravelRequestStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public TravelRequest $travelRequest,
        public TravelRequestStatus $oldStatus,
        public TravelRequestStatus $newStatus
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Load relationships if not already loaded
        if (!$this->travelRequest->relationLoaded('approver')) {
            $this->travelRequest->load('approver');
        }
        if (!$this->travelRequest->relationLoaded('canceller')) {
            $this->travelRequest->load('canceller');
        }

        $mailMessage = (new MailMessage)
            ->subject($this->getSubject())
            ->greeting('Olá ' . $notifiable->name . ',');

        if ($this->newStatus === TravelRequestStatus::APPROVED) {
            $mailMessage
                ->line('Seu pedido de viagem foi **aprovado**! 🎉')
                ->line('')
                ->line('**Detalhes da Viagem:**')
                ->line('📍 **Destino:** ' . $this->travelRequest->destination)
                ->line('📅 **Data de Ida:** ' . $this->travelRequest->departure_date->format('d/m/Y'))
                ->line('📅 **Data de Volta:** ' . $this->travelRequest->return_date->format('d/m/Y'))
                ->line('✅ **Aprovado por:** ' . ($this->travelRequest->approver->name ?? 'N/A'))
                ->line('🕐 **Aprovado em:** ' . $this->travelRequest->approved_at->format('d/m/Y H:i'))
                ->line('')
                ->line('Você já pode proceder com os preparativos para sua viagem corporativa.')
                ->success();
        } elseif ($this->newStatus === TravelRequestStatus::CANCELLED) {
            $mailMessage
                ->line('Informamos que seu pedido de viagem foi **cancelado**.')
                ->line('')
                ->line('**Detalhes da Viagem:**')
                ->line('📍 **Destino:** ' . $this->travelRequest->destination)
                ->line('📅 **Data de Ida:** ' . $this->travelRequest->departure_date->format('d/m/Y'))
                ->line('📅 **Data de Volta:** ' . $this->travelRequest->return_date->format('d/m/Y'))
                ->line('❌ **Cancelado por:** ' . ($this->travelRequest->canceller->name ?? 'N/A'))
                ->line('🕐 **Cancelado em:** ' . $this->travelRequest->cancelled_at->format('d/m/Y H:i'))
                ->line('')
                ->line('Se você tiver dúvidas ou precisar solicitar uma nova viagem, entre em contato com seu gestor ou o departamento de RH.')
                ->error();
        }

        $mailMessage->line('Obrigado por utilizar o Sistema de Gestão de Viagens Corporativas!');

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'travel_request_id' => $this->travelRequest->id,
            'destination' => $this->travelRequest->destination,
            'departure_date' => $this->travelRequest->departure_date->toDateString(),
            'return_date' => $this->travelRequest->return_date->toDateString(),
            'status' => $this->newStatus->value,
        ];
    }

    /**
     * Get the notification subject based on the new status.
     */
    private function getSubject(): string
    {
        return match ($this->newStatus) {
            TravelRequestStatus::APPROVED => '✅ Viagem Aprovada - ' . $this->travelRequest->destination,
            TravelRequestStatus::CANCELLED => '❌ Viagem Cancelada - ' . $this->travelRequest->destination,
            default => 'Atualização de Status - ' . $this->travelRequest->destination,
        };
    }
}
