<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdministratorController extends Controller
{
    public function index(Request $request): JsonResponse
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
}
