<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->admin, 403);

        return response()->json(User::query()
            ->orderBy('first_name')->orderBy('last_name')->orderBy('id')
            ->get(['id', 'first_name', 'last_name', 'email', 'phone_number', 'admin']));
    }

    public function show(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->admin, 403);

        return response()->json($user->only([
            'id',
            'first_name',
            'last_name',
            'email',
            'phone_number',
            'admin',
        ]));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        DB::transaction(function () use ($request, $user): void {
            $oldEmail = $user->email;
            $user->fill($request->safe()->only(['first_name', 'last_name', 'email', 'phone_number']));

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
                DB::table('password_reset_tokens')->where('email', $oldEmail)->delete();
            }

            $user->save();
        });

        return response()->json(['message' => 'User updated successfully.']);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->admin, 403);

        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 409);
        }

        return DB::transaction(function () use ($user): JsonResponse {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if (
                $user->tickets()->where('status', '!=', 'resolved')->exists()
                || $user->assignedTickets()->where('status', '!=', 'resolved')->exists()
            ) {
                return response()->json([
                    'message' => 'This user cannot be deleted while they own or are assigned unresolved tickets. Resolve or reassign those tickets first.',
                ], 409);
            }

            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            // The foreign keys remove the user's own (resolved) tickets with their responses and notes,
            // keep responses and notes the user wrote on other tickets (their user_id becomes null),
            // and clear the user's assignments on remaining tickets.
            $user->delete();

            return response()->json(['message' => 'User deleted successfully.']);
        });
    }
}
