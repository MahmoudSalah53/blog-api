<?php

namespace App\Http\Controllers;

use App\Events\CommentAdded;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Validator;
use App\Traits\ApiResponse;

class CommentController extends Controller
{
    use ApiResponse;

    public function addNewComment(Request $request, $postId)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        try {

            $comment = new Comment();
            $comment->comment = $request->comment;
            $comment->post_id = $postId;
            $comment->user_id = auth()->user()->id;
            $comment->save();

            broadcast(new CommentAdded($comment))->toOthers();

            Cache::forget("post_$postId");

            return $this->success(['message' => 'Comment Created successfully.'], 201);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function deleteComment($commentId)
    {
        $comment = Comment::find($commentId);

        if (!$comment) {
            return $this->error('Comment not found.', 404);
        }

        if ($comment->user_id !== auth()->user()->id) {
            return $this->error(['message' => 'Unauthorized.'], 403);
        }

        try {
            $comment->delete();

            Cache::forget("post_{$comment->post_id}");

            return $this->success(['data' => ['message' => 'Comment Deleted successfully.']], 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}