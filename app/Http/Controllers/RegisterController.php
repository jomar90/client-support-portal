<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Mail\RegistrationConfirmation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RegisterController extends Controller
{
    public function store(RegisterRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request): void {
            // User's password cast hashes the password. Only validated fields are used.
            $user = new User($request->validated());
            $user->admin = false;
            $user->save();

            Mail::to($user->email)->send(new RegistrationConfirmation($user));
        });

        return response()->json([
            'message' => 'Your account has been created. A confirmation email has been sent. You can now log in.',
        ], 201);
    }
}
