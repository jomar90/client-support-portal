<?php

namespace Tests\Feature;

use App\Mail\TicketResponseCreated;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_ticket_with_categories(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/tickets', [
                'title' => 'Cannot log in',
                'description' => 'The login form returns an error.',
                'category_ids' => [$category->id],
            ])
            ->assertCreated()
            ->assertJsonStructure(['id', 'message']);

        $ticketId = $response->json('id');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticketId,
            'user_id' => $user->id,
            'title' => 'Cannot log in',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('category_ticket', [
            'ticket_id' => $ticketId,
            'category_id' => $category->id,
        ]);
    }

    public function test_user_cannot_create_a_ticket_with_invalid_categories(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/tickets', [
                'title' => 'Cannot log in',
                'description' => 'The login form returns an error.',
                'category_ids' => [999999],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_ids.0');

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_only_administrators_can_manage_categories(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['admin' => true]);

        $this->actingAs($user)
            ->getJson('/api/categories')
            ->assertOk();

        $this->postJson('/api/categories', ['title' => 'Billing'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson('/api/categories', ['title' => 'Billing'])
            ->assertCreated();

        $this->assertDatabaseHas('categories', ['title' => 'Billing']);
    }

    public function test_category_linked_to_a_ticket_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $category = Category::factory()->create();
        $ticket = Ticket::factory()->create();
        $ticket->categories()->attach($category);

        $this->actingAs($admin)
            ->deleteJson("/api/categories/{$category->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_administrator_response_notifies_the_ticket_owner(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $admin = User::factory()->create(['admin' => true]);
        $ticket = Ticket::factory()->for($owner, 'creator')->create();

        $this->actingAs($admin)
            ->postJson("/api/tickets/{$ticket->id}/responses", [
                'body' => 'We are investigating your issue.',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('responses', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'body' => 'We are investigating your issue.',
        ]);

        Mail::assertSent(TicketResponseCreated::class, function (TicketResponseCreated $mail) use ($owner, $ticket): bool {
            return $mail->hasTo($owner->email)
                && $mail->ticket->id === $ticket->id;
        });
    }

    public function test_normal_user_cannot_post_an_administrator_response(): void
    {
        Mail::fake();

        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'creator')->create();

        $this->actingAs($owner)
            ->postJson("/api/tickets/{$ticket->id}/responses", [
                'body' => 'Attempt to post a response.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('responses', 0);
        Mail::assertNothingSent();
    }
}
