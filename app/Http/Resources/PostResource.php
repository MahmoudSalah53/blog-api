<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'likes_count' => $this->likes_count ?? $this->like()->count(),
            'created_at' => $this->created_at->toDateTimeString(),
            'author' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],

            'tags' => $this->tags->pluck('name'),

            'comments' => $this->whenLoaded('comment', function () {
                return $this->comment->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'comment' => $comment->comment,
                        'user' => [
                            'id' => $comment->user->id ?? null,
                            'name' => $comment->user->name ?? null,
                        ],
                        'created_at' => $comment->created_at->toDateTimeString(),
                    ];
                });
            }),
        ];
    }
}
