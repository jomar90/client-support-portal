<?php

namespace Tests\Feature;

use App\Http\Resources\TicketResource;
use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class InternalNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrators_create_edit_and_delete_notes_without_changing_author_or_ticket(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $otherAdmin = User::factory()->create(['admin' => true]);
        $ticket = Ticket::factory()->create();
        $url = "/api/tickets/{$ticket->id}";
        $this->actingAs($admin)->postJson($url.'/notes', [
            'body' => 'Private investigation', 'user_id' => $otherAdmin->id, 'ticket_id' => 999,
        ])->assertCreated();
        $note = Note::sole();
        $this->assertSame($admin->id, $note->user_id);
        $this->assertSame($ticket->id, $note->ticket_id);
        $this->getJson($url)->assertOk()->assertJsonPath('notes.0.body', 'Private investigation');
        $this->actingAs($otherAdmin)->patchJson($url.'/notes/'.$note->id, [
            'body' => 'Updated investigation', 'user_id' => $otherAdmin->id, 'ticket_id' => 999,
        ])->assertOk();
        $this->assertDatabaseHas('notes', ['id' => $note->id, 'body' => 'Updated investigation',
            'ticket_id' => $ticket->id, 'user_id' => $admin->id]);
        $this->getJson($url)->assertJsonPath('notes.0.body', 'Updated investigation');
        $this->deleteJson($url.'/notes/'.$note->id)->assertOk();
        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
        $this->getJson($url)->assertJsonPath('notes', []);
    }

    public function test_normal_users_never_receive_notes_or_change_them(): void
    {
        $owner = User::factory()->create();
        $ticket = Ticket::factory()->for($owner, 'creator')->create();
        $note = Note::factory()->for($ticket)->create(['body' => 'Confidential note']);
        $url = "/api/tickets/{$ticket->id}";
        $this->actingAs($owner)->getJson($url)->assertOk()
            ->assertJsonMissingPath('notes')->assertDontSee('Confidential note');
        $this->getJson('/api/tickets')->assertOk()->assertJsonMissingPath('0.notes')->assertDontSee('Confidential note');
        $this->postJson($url.'/notes', ['body' => 'Forbidden'])->assertForbidden();
        $this->patchJson($url.'/notes/'.$note->id, ['body' => 'Forbidden'])->assertForbidden();
        $this->deleteJson($url.'/notes/'.$note->id)->assertForbidden();
        $this->assertDatabaseHas('notes', ['id' => $note->id, 'body' => 'Confidential note']);

        // The resource also protects notes if a later controller accidentally loads them.
        $ticket->load(['categories', 'creator', 'assignee', 'responses.user', 'notes.user']);
        $request = Request::create($url);
        $request->setUserResolver(fn () => $owner);
        $this->assertArrayNotHasKey('notes', (new TicketResource($ticket))->resolve($request));
    }

    public function test_notes_are_chronological_only_on_the_correct_admin_detail_page(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $ticket = Ticket::factory()->create();
        $later = Note::factory()->for($ticket)->create(['created_at' => now()]);
        $earlier = Note::factory()->for($ticket)->create(['created_at' => now()->subDay()]);
        $different = Ticket::factory()->create();
        Note::factory()->for($different)->create(['body' => 'Other ticket secret']);
        $result = $this->actingAs($admin)->getJson('/api/tickets/'.$ticket->id)->assertOk()
            ->assertJsonCount(2, 'notes')->assertDontSee('Other ticket secret');
        $this->assertSame([$earlier->id, $later->id], array_column($result->json('notes'), 'id'));
        $this->getJson('/api/tickets')->assertOk()->assertJsonMissingPath('0.notes');
        $this->patchJson("/api/tickets/{$different->id}/notes/{$later->id}", ['body' => 'Wrong'])->assertNotFound();
        $this->deleteJson("/api/tickets/{$different->id}/notes/{$later->id}")->assertNotFound();
        $this->assertModelExists($later);
    }

    public function test_empty_notes_are_rejected_and_guests_cannot_use_note_routes(): void
    {
        $ticket = Ticket::factory()->create();
        $note = Note::factory()->for($ticket)->create();
        $url = "/api/tickets/{$ticket->id}/notes";
        $this->postJson($url, ['body' => 'Secret'])->assertUnauthorized();
        $this->patchJson($url.'/'.$note->id, ['body' => 'Secret'])->assertUnauthorized();
        $this->deleteJson($url.'/'.$note->id)->assertUnauthorized();
        $this->actingAs(User::factory()->create(['admin' => true]));
        $this->postJson($url, ['body' => ' '])->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->patchJson($url.'/'.$note->id, [])->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->assertDatabaseCount('notes', 1);
    }
}
