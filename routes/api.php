<?php

use App\Http\Controllers\AdministratorController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get(
    '/tickets',
    [TicketController::class, 'index']
);

Route::middleware('auth:sanctum')->post(
    '/tickets',
    [TicketController::class, 'store']
);

Route::middleware('auth:sanctum')->get(
    '/tickets/{ticket}',
    [TicketController::class, 'show']
);

Route::middleware('auth:sanctum')->patch(
    '/tickets/{ticket}',
    [TicketController::class, 'update']
);

Route::middleware('auth:sanctum')->get(
    '/administrators',
    [AdministratorController::class, 'index']
);

Route::middleware('auth:sanctum')->get(
    '/categories',
    [CategoryController::class, 'index']
);

Route::middleware('auth:sanctum')->post(
    '/categories',
    [CategoryController::class, 'store']
);

Route::middleware('auth:sanctum')->delete(
    '/categories/{category}',
    [CategoryController::class, 'destroy']
);

Route::middleware('auth:sanctum')->post(
    '/tickets/{ticket}/responses',
    [ResponseController::class, 'store']
);

Route::middleware('auth:sanctum')->patch(
    '/tickets/{ticket}/responses/{response}',
    [ResponseController::class, 'update']
);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/tickets/{ticket}/notes', [NoteController::class, 'store']);
    Route::patch('/tickets/{ticket}/notes/{note}', [NoteController::class, 'update']);
    Route::delete('/tickets/{ticket}/notes/{note}', [NoteController::class, 'destroy']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::patch('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});
