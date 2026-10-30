<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveNoteRequest;
use App\Models\Note;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function store(SaveNoteRequest $request, Ticket $ticket): JsonResponse
    {
        $note = $ticket->notes()->make($request->validated());
        $note->user()->associate($request->user());
        $note->save();

        return response()->json(['message' => 'Note created successfully.'], 201);
    }

    public function update(SaveNoteRequest $request, Ticket $ticket, Note $note): JsonResponse
    {
        abort_unless($note->ticket_id === $ticket->id, 404);

        $note->update($request->validated());

        return response()->json(['message' => 'Note updated successfully.']);
    }

    public function destroy(Request $request, Ticket $ticket, Note $note): JsonResponse
    {
        abort_unless($request->user()?->admin, 403);
        abort_unless($note->ticket_id === $ticket->id, 404);

        $note->delete();

        return response()->json(['message' => 'Note deleted successfully.']);
    }
}
