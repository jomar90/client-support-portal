<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $ticket = $this->route('ticket');

        if ($user === null || ! $ticket instanceof Ticket || ! $user->can('update', $ticket)) {
            return false;
        }

        // Reject the whole request if a normal user submits administrator-only fields.
        if ($this->hasAny(['status', 'assigned_to']) && ! $user->admin) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            'status' => ['sometimes', 'required', Rule::in(['pending', 'in_progress', 'resolved'])],
            'assigned_to' => [
                'sometimes', 'required', 'integer',
                Rule::exists('users', 'id')->where('admin', true),
            ],
        ];
    }
}
