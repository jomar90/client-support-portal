<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory()->state([
                'admin' => true,
            ]),
            'body' => fake()->paragraphs(2, true),
        ];
    }
}
