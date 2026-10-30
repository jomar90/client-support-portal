<?php

namespace Tests\Feature;

use App\Models\Category;
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
        $url = "/api/tickets/{$ticket->id}";

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
            ->patchJson("/api/tickets/{$ticket->id}", [
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
        $url = "/api/tickets/{$ticket->id}";

        $this->actingAs($user)
            ->patchJson($url, ['status' => 'resolved'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patchJson($url, ['status' => 'resolved'])
            ->assertOk();

        $this->getJson("/api/tickets/{$ticket->id}")
            ->assertOk()->assertJsonPath('status', 'resolved');

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
            ->patchJson("/api/tickets/{$ticket->id}", [
                'status' => 'closed',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'pending',
        ]);
    }

    public function test_owner_can_submit_partial_details_and_omitted_fields_stay_unchanged(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'creator')->create();
        $description = $ticket->description;
        $this->actingAs($owner)->patchJson("/api/tickets/{$ticket->id}", ['title' => 'Only the title'])
            ->assertOk();
        $this->assertSame('Only the title', $ticket->fresh()->title);
        $this->assertSame($description, $ticket->fresh()->description);
        $this->patchJson("/api/tickets/{$ticket->id}", ['description' => 'Only the description'])->assertOk();
        $this->assertSame('Only the title', $ticket->fresh()->title);
        $this->assertSame('Only the description', $ticket->fresh()->description);
        $this->patchJson("/api/tickets/{$ticket->id}", ['title' => ''])->assertUnprocessable();
        $this->patchJson("/api/tickets/{$ticket->id}", ['description' => null])->assertUnprocessable();
    }

    public function test_owner_cannot_submit_administrator_fields_even_when_null_or_combined_with_details(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['admin' => true]);
        $ticket = Ticket::factory()->for($owner, 'creator')->create(['title' => 'Original']);
        foreach ([['status' => 'pending'], ['status' => null], ['assigned_to' => null],
            ['assigned_to' => $admin->id], ['title' => 'Forbidden', 'status' => 'resolved'],
            ['title' => 'Forbidden', 'assigned_to' => $admin->id]] as $data) {
            $this->actingAs($owner)->patchJson("/api/tickets/{$ticket->id}", $data)->assertForbidden();
        }
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'title' => 'Original',
            'status' => 'pending', 'assigned_to' => null]);
    }

    public function test_administrator_can_update_all_ticket_fields_together(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $other = User::factory()->create(['admin' => true]);
        $ticket = Ticket::factory()->create();
        $ownerId = $ticket->user_id;
        $category = Category::factory()->create();
        $this->actingAs($admin)->patchJson("/api/tickets/{$ticket->id}", [
            'title' => 'All fields', 'description' => 'Updated together',
            'category_ids' => [$category->id], 'status' => 'in_progress', 'assigned_to' => $other->id,
        ])->assertOk();
        $this->getJson("/api/tickets/{$ticket->id}")->assertOk()
            ->assertJsonPath('title', 'All fields')->assertJsonPath('description', 'Updated together')
            ->assertJsonPath('status', 'in_progress')->assertJsonPath('assigned_to_id', $other->id)
            ->assertJsonPath('category_ids.0', $category->id);
        $this->assertSame($ownerId, $ticket->fresh()->user_id);
    }

    public function test_invalid_admin_update_is_atomic_and_does_not_clear_existing_assignment(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $ticket = Ticket::factory()->create(['title' => 'Original', 'assigned_to' => $admin->id]);
        foreach ([['title' => 'Invalid', 'status' => 'closed'], ['assigned_to' => 999999],
            ['assigned_to' => null], ['title' => 'Invalid', 'category_ids' => [999999]]] as $data) {
            $this->actingAs($admin)->patchJson("/api/tickets/{$ticket->id}", $data)->assertUnprocessable();
        }
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'title' => 'Original',
            'status' => 'pending', 'assigned_to' => $admin->id]);
        $this->patchJson("/api/tickets/{$ticket->id}", ['status' => 'resolved'])->assertOk();
        $this->assertSame($admin->id, $ticket->fresh()->assigned_to);
    }

    public function test_administrator_lookup_returns_only_admin_users_and_no_private_fields(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $other = User::factory()->create(['admin' => true]);
        User::factory()->create();
        $result = $this->actingAs($admin)->getJson('/api/administrators')->assertOk()->assertJsonCount(2);
        $ids = array_column($result->json(), 'id');
        $this->assertEqualsCanonicalizing([$admin->id, $other->id], $ids);
        $result->assertJsonStructure([['id', 'first_name', 'last_name']])
            ->assertJsonMissingPath('0.password')->assertJsonMissingPath('0.email');
    }

    public function test_redundant_routes_are_removed(): void
    {
        $uris = collect(app('router')->getRoutes())->map(fn ($route) => $route->uri())->all();
        $this->assertNotContains('api/ticket-categories', $uris);
        $this->assertNotContains('api/tickets/{ticket}/status', $uris);
        $this->assertNotContains('api/tickets/{ticket}/assignment', $uris);
    }
}
