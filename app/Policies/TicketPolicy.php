<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->admin || $ticket->user_id === $user->id;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->admin;
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $user->admin;
    }
}
