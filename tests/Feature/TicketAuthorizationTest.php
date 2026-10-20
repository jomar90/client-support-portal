<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_tickets(): void
    {
        $this->getJson('/api/tickets')->assertUnauthorized();
    }

    public function test_user_sees_only_their_own_tickets(): void
    {
        $user = User::factory()->create();
        $ownTicket = Ticket::factory()->for($user, 'creator')->create();
        Ticket::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $ownTicket->id);
    }

    public function test_user_can_view_their_own_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->for($user, 'creator')->create();

        $this->actingAs($user)
            ->getJson("/api/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('id', $ticket->id)
            ->assertJsonPath('title', $ticket->title)
            ->assertJsonStructure(['description', 'responses']);
    }

    public function test_user_cannot_view_another_users_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($user)
            ->getJson("/api/tickets/{$ticket->id}")
            ->assertForbidden();
    }

    public function test_user_can_edit_their_own_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->for($user, 'creator')->create();

        $this->actingAs($user)
            ->patchJson("/api/tickets/{$ticket->id}", [
                'title' => 'Updated title',
                'description' => 'Updated description',
            ])
            ->assertOk();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'title' => 'Updated title',
            'description' => 'Updated description',
        ]);
    }

    public function test_user_cannot_edit_another_users_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->create([
            'title' => 'Original title',
        ]);

        $this->actingAs($user)
            ->patchJson("/api/tickets/{$ticket->id}", [
                'title' => 'Unauthorized change',
                'description' => 'Unauthorized description',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'title' => 'Original title',
        ]);
    }

    public function test_administrator_can_view_and_edit_any_ticket(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $ticket = Ticket::factory()->create();

        $this->actingAs($admin)
            ->getJson("/api/tickets/{$ticket->id}")
            ->assertOk();

        $this->patchJson("/api/tickets/{$ticket->id}", [
            'title' => 'Admin updated title',
            'description' => 'Admin updated description',
        ])->assertOk();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'title' => 'Admin updated title',
        ]);
    }

    public function test_only_administrators_can_assign_tickets(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['admin' => true]);
        $ticket = Ticket::factory()->for($user, 'creator')->create();
        $url = "/api/tickets/{$ticket->id}/assignment";

        $this->actingAs($user)
            ->patchJson($url, ['assigned_to' => $admin->id])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patchJson($url, ['assigned_to' => $admin->id])
            ->assertOk();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_to' => $admin->id,
        ]);
    }

    public function test_ticket_cannot_be_assigned_to_a_normal_user(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $normalUser = User::factory()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($admin)
            ->patchJson("/api/tickets/{$ticket->id}/assignment", [
                'assigned_to' => $normalUser->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('assigned_to');

        $this->assertNull($ticket->fresh()->assigned_to);
    }

    public function test_only_administrators_can_change_ticket_status(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['admin' => true]);
        $ticket = Ticket::factory()->for($user, 'creator')->create();
        $url = "/api/tickets/{$ticket->id}/status";

        $this->actingAs($user)
            ->patchJson($url, ['status' => 'resolved'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patchJson($url, ['status' => 'resolved'])
            ->assertOk()
            ->assertJsonPath('status', 'resolved');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'resolved',
        ]);
    }

    public function test_invalid_ticket_status_is_rejected(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $ticket = Ticket::factory()->create();

        $this->actingAs($admin)
            ->patchJson("/api/tickets/{$ticket->id}/status", [
                'status' => 'closed',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'pending',
        ]);
    }
}
