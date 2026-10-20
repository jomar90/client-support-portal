<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => [
                'integer',
                'distinct',
                'exists:categories,id',
            ],
        ];
    }
}
