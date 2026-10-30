<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // The detail endpoint loads responses.
        $isDetail = $this->resource->relationLoaded('responses');

        $categoryTitles = [];
        $categoryIds = [];
        foreach ($this->categories as $category) {
            $categoryTitles[] = $category->title;
            $categoryIds[] = $category->id;
        }

        $assignedTo = null;
        if ($this->assignee) {
            $assignedTo = trim($this->assignee->first_name.' '.$this->assignee->last_name);
        }

        $data = [];
        $data['id'] = $this->id;
        $data['title'] = $this->title;

        if ($isDetail) {
            $data['description'] = $this->description;
        }

        $data['categories'] = $categoryTitles;

        if ($isDetail) {
            $data['category_ids'] = $categoryIds;
            $data['assigned_to_id'] = $this->resource->assigned_to;
        }

        $data['status'] = $this->status;
        $data['created_by'] = trim($this->creator->first_name.' '.$this->creator->last_name);
        $data['created_at'] = $this->created_at;
        $data['updated_at'] = $this->updated_at;
        $data['assigned_to'] = $assignedTo;

        // Internal notes are only added for administrators, and only when the controller loaded them.
        if ($request->user()?->admin && $this->resource->relationLoaded('notes')) {
            $notes = [];
            foreach ($this->notes as $note) {
                // A note keeps its text when its author is deleted.
                $author = 'Deleted user';
                if ($note->user) {
                    $author = trim($note->user->first_name.' '.$note->user->last_name);
                }

                $notes[] = [
                    'id' => $note->id,
                    'body' => $note->body,
                    'created_by' => $author,
                    'created_at' => $note->created_at,
                    'updated_at' => $note->updated_at,
                ];
            }
            $data['notes'] = $notes;
        }

        if ($isDetail) {
            $responses = [];
            foreach ($this->responses as $response) {
                // A response keeps its text when its author is deleted.
                $author = 'Deleted user';
                if ($response->user) {
                    $author = trim($response->user->first_name.' '.$response->user->last_name);
                }

                $responses[] = [
                    'id' => $response->id,
                    'body' => $response->body,
                    'created_by' => $author,
                    'created_at' => $response->created_at,
                    'updated_at' => $response->updated_at,
                ];
            }
            $data['responses'] = $responses;
        }

        return $data;
    }
}
