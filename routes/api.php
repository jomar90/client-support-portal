<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\CategoryController;

use App\Http\Controllers\ResponseController;



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
    [TicketController::class, 'administrators']
);

Route::middleware('auth:sanctum')->patch(
    '/tickets/{ticket}/assignment',
    [TicketController::class, 'assign']
);

Route::middleware('auth:sanctum')->patch(
    '/tickets/{ticket}/status',
    [TicketController::class, 'updateStatus']
);

Route::middleware('auth:sanctum')->get(
    '/categories',
    [CategoryController::class, 'index']
);

Route::middleware('auth:sanctum')->get(
    '/ticket-categories',
    [CategoryController::class, 'selectable']
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
