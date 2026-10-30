<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'body',
])]
class Response extends Model
{
    use HasFactory;

    /**
     * Get the ticket that the response belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who wrote the response.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
