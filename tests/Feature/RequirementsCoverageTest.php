<?php

namespace Tests\Feature;

use App\Mail\TicketResponseCreated;
use App\Models\Category;
use App\Models\Note;
use App\Models\Response;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RequirementsCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_data_and_chronological_administrator_overview(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertFalse(User::where('email', 'user@example.com')->firstOrFail()->admin);
        $tickets = $this->actingAs($admin)->getJson('/api/tickets')->assertOk()->assertJsonCount(4)
            ->assertJsonStructure([['id', 'title', 'categories', 'status', 'created_by', 'created_at', 'updated_at', 'assigned_to']])->json();
        $expected = Ticket::orderBy('created_at')->orderBy('id')->pluck('id')->all();
        $this->assertSame($expected, array_column($tickets, 'id'));
        $this->assertDatabaseCount('responses', 2);
    }

    public function test_categories_are_alphabetical_and_unused_category_can_be_deleted(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $z = Category::factory()->create(['title' => 'Zebra']);
        Category::factory()->create(['title' => 'Account']);
        $this->actingAs($admin)->getJson('/api/categories')->assertOk()->assertJsonPath('0.title', 'Account');
        $this->deleteJson('/api/categories/'.$z->id)->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $z->id]);
        $this->postJson('/api/categories', ['title' => 'Account'])->assertUnprocessable();
        $this->postJson('/api/categories', ['title' => ''])->assertUnprocessable();
        $user = User::factory()->create();
        $category = Category::firstOrFail();
        $this->actingAs($user)->deleteJson('/api/categories/'.$category->id)->assertForbidden();
        $this->getJson('/api/categories')->assertOk();
        $this->getJson('/api/administrators')->assertForbidden();
    }

    public function test_ticket_creation_ignores_forged_owner_status_and_assignment(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['admin' => true]);
        $id = $this->actingAs($user)->postJson('/api/tickets', [
            'title' => 'Help', 'description' => 'Please help', 'user_id' => $other->id,
            'status' => 'resolved', 'assigned_to' => $other->id,
        ])->assertCreated()->json('id');
        $this->assertDatabaseHas('tickets', ['id' => $id, 'user_id' => $user->id,
            'status' => 'pending', 'assigned_to' => null]);
        $this->postJson('/api/tickets', [])->assertUnprocessable()->assertJsonValidationErrors(['title', 'description']);
        $this->patchJson('/api/tickets/'.$id, ['title' => 'Changed', 'description' => 'Changed',
            'user_id' => $other->id, 'status' => 'resolved', 'assigned_to' => $other->id])->assertForbidden();
        $this->assertSame('Help', Ticket::findOrFail($id)->title);
        $this->patchJson('/api/tickets/'.$id, ['title' => 'Changed', 'description' => 'Changed',
            'user_id' => $other->id])->assertOk();
        $this->assertDatabaseHas('tickets', ['id' => $id, 'user_id' => $user->id,
            'status' => 'pending', 'assigned_to' => null]);
    }

    public function test_ticket_edit_can_change_categories_and_invalid_edit_does_not_partly_save(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->for($user, 'creator')->create();
        $category = Category::factory()->create();
        $this->actingAs($user)->patchJson('/api/tickets/'.$ticket->id, [
            'title' => 'Changed', 'description' => 'Changed', 'category_ids' => [$category->id],
        ])->assertOk();
        $this->getJson('/api/tickets/'.$ticket->id)->assertOk()->assertJsonPath('category_ids.0', $category->id);
        $this->patchJson('/api/tickets/'.$ticket->id, [
            'title' => 'Invalid', 'description' => 'Changed', 'category_ids' => [999999],
        ])->assertUnprocessable();
        $this->assertSame('Changed', $ticket->fresh()->title);
        $this->patchJson('/api/tickets/'.$ticket->id, [
            'title' => 'Changed', 'description' => 'Changed', 'category_ids' => [],
        ])->assertOk();
        $this->assertSame(0, $ticket->categories()->count());
    }

    public function test_administrator_can_assign_self_another_admin_and_all_supported_statuses(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $other = User::factory()->create(['admin' => true]);
        $ticket = Ticket::factory()->create();
        foreach ([$admin, $other] as $assignee) {
            $this->actingAs($admin)->patchJson('/api/tickets/'.$ticket->id.'', ['assigned_to' => $assignee->id])->assertOk();
            $this->assertSame($assignee->id, $ticket->fresh()->assigned_to);
        }
        foreach (['pending', 'in_progress', 'resolved'] as $status) {
            $this->patchJson('/api/tickets/'.$ticket->id.'', ['status' => $status])->assertOk();
            $this->assertSame($status, $ticket->fresh()->status);
        }
        $this->getJson('/api/administrators')->assertOk()->assertJsonCount(2);
    }

    public function test_responses_can_be_edited_and_only_authorized_viewers_read_them(): void
    {
        Mail::fake();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->create(['admin' => true]);
        $ticket = Ticket::factory()->for($owner, 'creator')->create();
        $response = Response::factory()->for($ticket)->for($admin)->create(['body' => 'Original']);
        Note::factory()->for($ticket)->for($admin)->create(['body' => 'Private foundation data']);
        $url = '/api/tickets/'.$ticket->id;
        $this->actingAs($owner)->patchJson($url.'/responses/'.$response->id, ['body' => 'Forbidden'])->assertForbidden();
        $this->actingAs($admin)->patchJson($url.'/responses/'.$response->id, ['body' => 'Updated'])->assertOk();
        $this->assertSame('Updated', $response->fresh()->body);
        $this->patchJson($url.'/responses/'.$response->id, ['body' => ''])->assertUnprocessable();
        $this->postJson($url.'/responses', ['body' => ''])->assertUnprocessable();
        $differentTicket = Ticket::factory()->create();
        $this->patchJson('/api/tickets/'.$differentTicket->id.'/responses/'.$response->id, ['body' => 'Wrong ticket'])->assertNotFound();
        $this->actingAs($owner)->getJson($url)->assertOk()->assertJsonPath('responses.0.body', 'Updated')
            ->assertJsonMissingPath('notes')->assertDontSee('Private foundation data');
        // Complete version adds administrator-only notes (requirements 19–20).
        $this->actingAs($admin)->getJson($url)->assertOk()->assertJsonPath('notes.0.body', 'Private foundation data');
        $this->actingAs($other)->getJson($url)->assertForbidden();
        Mail::assertNothingSent(); // Edits do not send a second notification.
    }

    public function test_response_email_renders_ticket_and_response(): void
    {
        $ticket = Ticket::factory()->create();
        $response = Response::factory()->for($ticket)->create(['body' => 'A helpful reply']);
        $mail = new TicketResponseCreated($ticket, $response);
        $mail->assertSeeInHtml('A helpful reply');
        $mail->assertSeeInHtml($ticket->title);
    }

    public function test_guest_cannot_call_any_workflow_endpoint(): void
    {
        $category = Category::factory()->create();
        $ticket = Ticket::factory()->create();
        $response = Response::factory()->for($ticket)->create();
        foreach (['/api/user', '/api/categories', '/api/administrators', '/api/tickets/'.$ticket->id] as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
        $this->postJson('/api/tickets', [])->assertUnauthorized();
        $this->patchJson('/api/tickets/'.$ticket->id, [])->assertUnauthorized();
        $this->patchJson('/api/tickets/'.$ticket->id, ['status' => 'resolved'])->assertUnauthorized();
        $this->patchJson('/api/tickets/'.$ticket->id, ['assigned_to' => $ticket->user_id])->assertUnauthorized();
        $this->postJson('/api/tickets/'.$ticket->id.'/responses', [])->assertUnauthorized();
        $this->patchJson('/api/tickets/'.$ticket->id.'/responses/'.$response->id, [])->assertUnauthorized();
        $this->postJson('/api/categories', [])->assertUnauthorized();
        $this->deleteJson('/api/categories/'.$category->id)->assertUnauthorized();
    }
}
