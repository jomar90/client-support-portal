<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResponseRequest;
use App\Http\Requests\UpdateResponseRequest;
use App\Mail\TicketResponseCreated;
use App\Models\Response;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class ResponseController extends Controller
{
    public function store(
        StoreResponseRequest $request,
        Ticket $ticket
    ): JsonResponse {
        $response = $ticket->responses()->make([
            'body' => $request->validated('body'),
        ]);

        $response->user()->associate($request->user());
        $response->save();

        $response->load(
            'user:id,first_name,last_name'
        );

        Mail::to($ticket->creator->email)->send(
            new TicketResponseCreated($ticket, $response)
        );

        return response()->json($response, 201);
    }

    public function update(
        UpdateResponseRequest $request,
        Ticket $ticket,
        Response $response
    ): JsonResponse {
        abort_unless(
            $response->ticket_id === $ticket->id,
            404
        );

        $response->body = $request->validated('body');
        $response->save();

        return response()->json([
            'id' => $response->id,
            'body' => $response->body,
            'updated_at' => $response->updated_at,
        ]);
    }
}
