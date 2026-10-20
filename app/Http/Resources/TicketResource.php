<?php

namespace App\Http\Resources;

use App\Models\Response;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // The detail endpoint loads responses.
        $isDetail = $this->resource->relationLoaded('responses');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->when(
                $isDetail,
                $this->description
            ),
            'categories' => $this->categories
                ->pluck('title')
                ->values()
                ->all(),
            'status' => $this->status,
            'created_by' => trim(
                $this->creator->first_name . ' ' .
                    $this->creator->last_name
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'assigned_to' => $this->assignee
                ? trim(
                    $this->assignee->first_name . ' ' .
                        $this->assignee->last_name
                )
                : null,
            'responses' => $this->when(
                $isDetail,
                fn() => $this->responses
                    ->map(fn(Response $response): array => [
                        'id' => $response->id,
                        'body' => $response->body,
                        'created_by' => trim(
                            $response->user->first_name . ' ' .
                                $response->user->last_name
                        ),
                        'created_at' => $response->created_at,
                        'updated_at' => $response->updated_at,
                    ])
                    ->all()
            ),
        ];
    }
}
