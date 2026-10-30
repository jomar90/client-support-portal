<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::query()->with([
            'categories:id,title',
            'creator:id,first_name,last_name',
            'assignee:id,first_name,last_name',
        ]);

        if (! $request->user()->admin) {
            $query->where('user_id', $request->user()->id);
        }

        $tickets = $query
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return response()->json(
            TicketResource::collection($tickets)->resolve()
        );
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $data = $request->validated();

        $categoryIds = $data['category_ids'] ?? [];

        unset($data['category_ids']);

        $ticket = DB::transaction(function () use (
            $request,
            $data,
            $categoryIds
        ): Ticket {
            $ticket = $request->user()->tickets()->create($data);

            $ticket->categories()->sync($categoryIds);

            return $ticket;
        });

        return response()->json([
            'id' => $ticket->id,
            'message' => 'Ticket created successfully.',
        ], 201);
    }

    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        Gate::authorize('view', $ticket);

        $ticket->load([
            'categories:id,title',
            'creator:id,first_name,last_name',
            'assignee:id,first_name,last_name',
            'responses' => fn ($query) => $query
                ->with('user:id,first_name,last_name')
                ->orderBy('created_at')
                ->orderBy('id'),
        ]);

        if ($request->user()->admin) {
            $ticket->load(['notes' => fn ($query) => $query
                ->with('user:id,first_name,last_name')
                ->orderBy('created_at')->orderBy('id')]);
        }

        return response()->json(
            (new TicketResource($ticket))->resolve()
        );
    }

    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket
    ): JsonResponse {
        $data = $request->validated();

        DB::transaction(function () use ($ticket, $data): void {
            if (array_key_exists('title', $data)) {
                $ticket->title = $data['title'];
            }
            if (array_key_exists('description', $data)) {
                $ticket->description = $data['description'];
            }
            if (array_key_exists('status', $data)) {
                $ticket->status = $data['status'];
            }
            if (array_key_exists('assigned_to', $data)) {
                $ticket->assigned_to = $data['assigned_to'];
            }
            $ticket->save();

            if (array_key_exists('category_ids', $data)) {
                $ticket->categories()->sync($data['category_ids']);
            }
        });

        return response()->json([
            'message' => 'Ticket updated successfully.',
        ]);
    }
}
