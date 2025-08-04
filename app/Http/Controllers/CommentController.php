<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
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

            // تم تصحيح الاستجابة لتكون object بدلاً من string
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

        // تم تصحيح id() إلى id
        if ($comment->user_id !== auth()->user()->id) {
            return $this->error(['message' => 'Unauthorized.'], 403);
        }

        try {
            $comment->delete();

            return $this->success(['data' => ['message' => 'Comment Deleted successfully.']], 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}