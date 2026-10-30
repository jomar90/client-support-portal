<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Note;
use App\Models\Response;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_list_view_edit_or_delete_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach (['/api/users', '/api/users/'.$other->id] as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
        $this->patchJson('/api/users/'.$other->id, [])->assertUnauthorized();
        $this->deleteJson('/api/users/'.$other->id)->assertUnauthorized();
        $this->actingAs($user);
        $this->getJson('/api/users')->assertForbidden();
        $this->getJson('/api/users/'.$other->id)->assertForbidden();
        $this->patchJson('/api/users/'.$other->id, ['first_name' => 'Changed'])->assertForbidden();
        $this->patchJson('/api/users/'.$user->id, ['admin' => true])->assertForbidden();
        $this->deleteJson('/api/users/'.$other->id)->assertForbidden();
        $this->assertFalse($user->fresh()->admin);
        $this->assertModelExists($other);
    }

    public function test_administrator_reads_required_fields_and_edits_contact_details(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $user = User::factory()->create();
        $oldEmail = $user->email;
        DB::table('password_reset_tokens')->insert(['email' => $oldEmail, 'token' => 'old-token']);
        $this->actingAs($admin)->getJson('/api/users')->assertOk()->assertJsonCount(2)
            ->assertJsonStructure([['id', 'first_name', 'last_name', 'email', 'admin', 'phone_number']])
            ->assertJsonMissingPath('0.password')->assertJsonMissingPath('0.remember_token');
        $url = '/api/users/'.$user->id;
        $this->getJson($url)->assertOk()->assertJsonPath('admin', false)->assertJsonMissingPath('password');
        $this->patchJson($url, [
            'first_name' => 'Updated',
            'last_name' => 'Customer',
            'email' => 'updated@example.com',
            'phone_number' => '+31 12345678',
        ])->assertOk();
        $this->getJson($url)->assertJsonPath('first_name', 'Updated')
            ->assertJsonPath('phone_number', '+31 12345678')->assertJsonPath('email', 'updated@example.com');
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $oldEmail]);
        $this->assertFalse($user->fresh()->admin);
    }

    public function test_invalid_user_edit_does_not_save_and_role_changes_are_not_supported(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $user = User::factory()->create();
        $url = '/api/users/'.$user->id;
        $data = ['first_name' => 'Valid', 'last_name' => 'Name', 'email' => $user->email, 'phone_number' => null];
        $this->actingAs($admin)->patchJson($url, $data)->assertOk();
        foreach (
            [
                ['email' => $admin->email],
                ['email' => 'bad-email'],
                ['first_name' => ''],
                ['last_name' => str_repeat('a', 256)],
                ['phone_number' => ['bad']],
                ['admin' => true],
                ['password' => 'new-password'],
            ] as $invalid
        ) {
            $this->patchJson($url, array_merge($data, $invalid))->assertUnprocessable();
        }
        $this->assertDatabaseHas('users', ['id' => $user->id, 'first_name' => 'Valid', 'email' => $user->email, 'admin' => false]);
        $this->assertTrue(password_verify('password', $user->fresh()->password));
    }

    public function test_owned_or_assigned_unresolved_tickets_prevent_deletion(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $owner = User::factory()->create();
        $assignee = User::factory()->create(['admin' => true]);
        $ticket = Ticket::factory()->for($owner, 'creator')->create(['assigned_to' => $assignee->id]);
        foreach (['pending', 'in_progress'] as $status) {
            $ticket->status = $status;
            $ticket->save();
            foreach ([$owner, $assignee] as $user) {
                $this->actingAs($admin)->deleteJson('/api/users/'.$user->id)->assertConflict()
                    ->assertJsonPath('message', 'This user cannot be deleted while they own or are assigned unresolved tickets. Resolve or reassign those tickets first.');
                $this->assertModelExists($user);
                $this->assertModelExists($ticket);
            }
        }
        $this->deleteJson('/api/users/'.$admin->id)->assertConflict();
        $this->assertModelExists($admin);
    }

    public function test_permitted_deletion_follows_foreign_keys_and_removes_session_and_reset_data(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $user = User::factory()->create(['admin' => true]);
        $owned = Ticket::factory()->for($user, 'creator')->create(['status' => 'resolved']);
        $remaining = Ticket::factory()->create(['status' => 'resolved', 'assigned_to' => $user->id]);
        $category = Category::factory()->create();
        $owned->categories()->attach($category);
        $ownedResponse = Response::factory()->for($owned)->for($admin)->create();
        $ownedNote = Note::factory()->for($owned)->for($admin)->create();
        DB::table('sessions')->insert(['id' => 'deleted-user-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => 'old-token']);
        $this->actingAs($admin)->deleteJson('/api/users/'.$user->id)->assertOk();
        // The deleted user's own resolved ticket is still removed, together with its responses and notes.
        foreach ([$user, $owned, $ownedResponse, $ownedNote] as $model) {
            $this->assertModelMissing($model);
        }
        $this->assertNull($remaining->fresh()->assigned_to);
        $this->assertModelExists($category);
        $this->assertDatabaseMissing('category_ticket', ['ticket_id' => $owned->id]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_deleting_an_administrator_keeps_their_responses_and_notes_on_other_tickets(): void
    {
        $admin = User::factory()->create(['admin' => true]);
        $formerAdmin = User::factory()->create(['admin' => true]);
        $customer = User::factory()->create();
        $openTicket = Ticket::factory()->for($customer, 'creator')->create(['status' => 'pending']);
        $response = Response::factory()->for($openTicket)->for($formerAdmin)->create(['body' => 'Reply from former admin']);
        $note = Note::factory()->for($openTicket)->for($formerAdmin)->create(['body' => 'Note from former admin']);

        // The former admin neither owns nor is assigned an unresolved ticket, so deletion is allowed.
        $this->actingAs($admin)->deleteJson('/api/users/'.$formerAdmin->id)->assertOk();
        $this->assertModelMissing($formerAdmin);

        // The customer's ticket keeps its response and note; only the author reference is cleared.
        $this->assertModelExists($openTicket);
        $this->assertDatabaseHas('responses', ['id' => $response->id, 'ticket_id' => $openTicket->id, 'user_id' => null]);
        $this->assertDatabaseHas('notes', ['id' => $note->id, 'ticket_id' => $openTicket->id, 'user_id' => null]);

        // Administrators see both, with a fallback author name.
        $this->getJson('/api/tickets/'.$openTicket->id)->assertOk()
            ->assertJsonPath('responses.0.body', 'Reply from former admin')
            ->assertJsonPath('responses.0.created_by', 'Deleted user')
            ->assertJsonPath('notes.0.body', 'Note from former admin')
            ->assertJsonPath('notes.0.created_by', 'Deleted user');

        // The customer still sees the response but never the internal note.
        $this->actingAs($customer)->getJson('/api/tickets/'.$openTicket->id)->assertOk()
            ->assertJsonPath('responses.0.created_by', 'Deleted user')
            ->assertJsonMissingPath('notes')
            ->assertDontSee('Note from former admin');
    }

    public function test_user_without_tickets_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['admin' => true]);
        $this->actingAs($admin)->deleteJson('/api/users/'.$user->id)->assertOk();
        $this->assertModelMissing($user);
    }
}
