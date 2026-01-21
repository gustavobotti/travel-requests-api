<?php

namespace Tests\Feature\Api;

use App\Enums\TravelRequestStatus;
use App\Events\TravelRequestStatusUpdated;
use App\Models\TravelRequest;
use App\Models\User;
use App\Notifications\TravelRequestStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TravelRequestNotificationTest extends TestCase
{
    use RefreshDatabase;

    private string $baseUrl = '/api/v1/travel-requests';

    /* ========================================
     * NOTIFICATION ON APPROVAL - Requisito: "Sempre que um pedido for
     * aprovado, uma notificação deve ser enviada para o usuário que
     * solicitou o pedido"
     * ======================================== */

    #[Test]
    public function notification_is_sent_when_travel_request_is_approved(): void
    {
        Notification::fake();

        $requester = $this->createUser(['name' => 'Requester User', 'email' => 'requester@example.com']);
        $approver = $this->actingAsUser(['name' => 'Approver User']);

        $travelRequest = $this->createTravelRequestFor($requester, [
            'status' => TravelRequestStatus::REQUESTED,
            'destination' => 'São Paulo',
        ]);

        $response = $this->patchJson(
            "{$this->baseUrl}/{$travelRequest->id}/status",
            ['status' => TravelRequestStatus::APPROVED->value]
        );

        $response->assertStatus(200);

        // Assert notification was sent to requester
        Notification::assertSentTo(
            $requester,
            TravelRequestStatusNotification::class,
            function ($notification, $channels) use ($travelRequest, $requester) {
                return $notification->travelRequest->id === $travelRequest->id
                    && $notification->oldStatus === TravelRequestStatus::REQUESTED
                    && $notification->newStatus === TravelRequestStatus::APPROVED
                    && in_array('mail', $channels);
            }
        );

        // Assert notification was NOT sent to approver
        Notification::assertNotSentTo($approver, TravelRequestStatusNotification::class);
    }

    /* ========================================
     * NOTIFICATION ON CANCELLATION - Requisito: "Sempre que um pedido for
     * cancelado, uma notificação deve ser enviada para o usuário que
     * solicitou o pedido"
     * ======================================== */

    #[Test]
    public function notification_is_sent_when_travel_request_is_cancelled(): void
    {
        Notification::fake();

        $requester = $this->createUser(['name' => 'Requester User', 'email' => 'requester@example.com']);
        $canceller = $this->actingAsUser(['name' => 'Canceller User']);

        $travelRequest = $this->createTravelRequestFor($requester, [
            'status' => TravelRequestStatus::REQUESTED,
            'destination' => 'Rio de Janeiro',
        ]);

        $response = $this->patchJson(
            "{$this->baseUrl}/{$travelRequest->id}/status",
            ['status' => TravelRequestStatus::CANCELLED->value]
        );

        $response->assertStatus(200);

        // Assert notification was sent to requester
        Notification::assertSentTo(
            $requester,
            TravelRequestStatusNotification::class,
            function ($notification, $channels) use ($travelRequest, $requester) {
                return $notification->travelRequest->id === $travelRequest->id
                    && $notification->oldStatus === TravelRequestStatus::REQUESTED
                    && $notification->newStatus === TravelRequestStatus::CANCELLED
                    && in_array('mail', $channels);
            }
        );

        // Assert notification was NOT sent to canceller
        Notification::assertNotSentTo($canceller, TravelRequestStatusNotification::class);
    }

    #[Test]
    public function notification_is_sent_when_approved_travel_request_is_cancelled(): void
    {
        Notification::fake();

        $requester = $this->createUser(['name' => 'Requester User']);
        $approver = $this->createUser(['name' => 'Approver User']);
        $canceller = $this->actingAsUser(['name' => 'Canceller User']);

        $travelRequest = $this->createTravelRequestFor($requester, [
            'status' => TravelRequestStatus::APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now()->subDays(1),
        ]);

        $response = $this->patchJson(
            "{$this->baseUrl}/{$travelRequest->id}/status",
            ['status' => TravelRequestStatus::CANCELLED->value]
        );

        $response->assertStatus(200);

        // Assert notification was sent to requester
        Notification::assertSentTo(
            $requester,
            TravelRequestStatusNotification::class,
            function ($notification) use ($travelRequest) {
                return $notification->travelRequest->id === $travelRequest->id
                    && $notification->oldStatus === TravelRequestStatus::APPROVED
                    && $notification->newStatus === TravelRequestStatus::CANCELLED;
            }
        );
    }

    /* ========================================
     * NO NOTIFICATION ON OTHER ACTIONS - Notificações só disparam quando status muda (aprovado/cancelado)
     * ======================================== */

    #[Test]
    public function notification_is_not_sent_when_travel_request_is_created(): void
    {
        Notification::fake();

        $user = $this->actingAsUser();

        $travelData = [
            'requester_name' => 'John Doe',
            'destination' => 'São Paulo',
            'departure_date' => now()->addDays(10)->format('Y-m-d'),
            'return_date' => now()->addDays(15)->format('Y-m-d'),
        ];

        $response = $this->postJson($this->baseUrl, $travelData);

        $response->assertStatus(201);

        Notification::assertNothingSent();
    }

    #[Test]
    public function notification_is_not_sent_when_travel_request_is_updated(): void
    {
        Notification::fake();

        $user = $this->actingAsUser();

        $travelRequest = $this->createTravelRequestFor($user, [
            'status' => TravelRequestStatus::REQUESTED,
        ]);

        $response = $this->putJson("{$this->baseUrl}/{$travelRequest->id}", [
            'destination' => 'Updated Destination',
        ]);

        $response->assertStatus(200);

        Notification::assertNothingSent();
    }

    /* ========================================
     * EVENT DISPATCHING
     * ======================================== */

    #[Test]
    public function event_is_dispatched_when_status_is_updated(): void
    {
        Event::fake([TravelRequestStatusUpdated::class]);

        $requester = $this->createUser();
        $approver = $this->actingAsUser();

        $travelRequest = $this->createTravelRequestFor($requester, [
            'status' => TravelRequestStatus::REQUESTED,
        ]);

        $response = $this->patchJson(
            "{$this->baseUrl}/{$travelRequest->id}/status",
            ['status' => TravelRequestStatus::APPROVED->value]
        );

        $response->assertStatus(200);

        Event::assertDispatched(TravelRequestStatusUpdated::class, function ($event) use ($travelRequest) {
            return $event->travelRequest->id === $travelRequest->id
                && $event->oldStatus === TravelRequestStatus::REQUESTED
                && $event->newStatus === TravelRequestStatus::APPROVED;
        });
    }

    /* ========================================
     * NOTIFICATION CONTENT
     * ======================================== */

    #[Test]
    public function notification_contains_correct_information_for_approval(): void
    {
        $requester = $this->createUser(['name' => 'John Requester']);
        $approver = $this->createUser(['name' => 'Jane Approver']);

        $travelRequest = $this->createTravelRequestFor($requester, [
            'status' => TravelRequestStatus::REQUESTED,
            'destination' => 'São Paulo, Brazil',
            'departure_date' => '2026-03-01',
            'return_date' => '2026-03-10',
        ]);

        $travelRequest->changeStatus(TravelRequestStatus::APPROVED, $approver);

        $notification = new TravelRequestStatusNotification(
            $travelRequest,
            TravelRequestStatus::REQUESTED,
            TravelRequestStatus::APPROVED
        );

        $mailData = $notification->toMail($requester)->toArray();

        $this->assertStringContainsString('aprovada', strtolower($mailData['subject']));

        // Verify destination is present in the email body
        $allLines = implode(' ', $mailData['introLines']);
        $this->assertStringContainsString('São Paulo, Brazil', $allLines);
    }

    #[Test]
    public function notification_contains_correct_information_for_cancellation(): void
    {
        $requester = $this->createUser(['name' => 'John Requester']);
        $canceller = $this->createUser(['name' => 'Jane Canceller']);

        $travelRequest = $this->createTravelRequestFor($requester, [
            'status' => TravelRequestStatus::REQUESTED,
            'destination' => 'Rio de Janeiro, Brazil',
            'departure_date' => '2026-03-01',
            'return_date' => '2026-03-10',
        ]);

        $travelRequest->changeStatus(TravelRequestStatus::CANCELLED, $canceller);

        $notification = new TravelRequestStatusNotification(
            $travelRequest,
            TravelRequestStatus::REQUESTED,
            TravelRequestStatus::CANCELLED
        );

        $mailData = $notification->toMail($requester)->toArray();

        $this->assertStringContainsString('cancelada', strtolower($mailData['subject']));

        // Verify destination is present in the email body
        $allLines = implode(' ', $mailData['introLines']);
        $this->assertStringContainsString('Rio de Janeiro, Brazil', $allLines);
    }
}

