<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

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
            ->orderByDesc('created_at')
            ->orderByDesc('id')
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

    public function show(Ticket $ticket): JsonResponse
    {
        Gate::authorize('view', $ticket);

        $ticket->load([
            'categories:id,title',
            'creator:id,first_name,last_name',
            'assignee:id,first_name,last_name',
            'responses' => fn($query) => $query
                ->with('user:id,first_name,last_name')
                ->orderBy('created_at')
                ->orderBy('id'),
        ]);

        return response()->json(
            (new TicketResource($ticket))->resolve()
        );
    }

    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket
    ): JsonResponse {
        $ticket->update($request->validated());

        return response()->json([
            'message' => 'Ticket updated successfully.',
        ]);
    }

    public function administrators(Request $request): JsonResponse
    {
        abort_unless($request->user()->admin, 403);

        return response()->json(
            User::query()
                ->where('admin', true)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name'])
        );
    }

    public function assign(Request $request, Ticket $ticket): JsonResponse
    {
        Gate::authorize('assign', $ticket);

        $validated = $request->validate([
            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('admin', true),
            ],
        ]);

        $ticket->assigned_to = $validated['assigned_to'];
        $ticket->save();

        return response()->json([
            'message' => 'Ticket assigned successfully.',
        ]);
    }

    public function updateStatus(Request $request, Ticket $ticket): JsonResponse
    {
        Gate::authorize('changeStatus', $ticket);

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['pending', 'in_progress', 'resolved']),
            ],
        ]);

        $ticket->status = $validated['status'];
        $ticket->save();

        return response()->json([
            'message' => 'Ticket status updated successfully.',
            'status' => $ticket->status,
        ]);
    }
}
