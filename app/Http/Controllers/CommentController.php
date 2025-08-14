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

    /**
     * @OA\Post(
     *     path="/api/comments/add/{postId}",
     *     tags={"Comments"},
     *     summary="Add new comment to a post",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="postId",
     *         in="path",
     *         required=true,
     *         description="ID of the post to comment on",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"comment"},
     *             @OA\Property(property="comment", type="string", example="This is a comment")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Comment created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Post not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */

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

    /**
     * @OA\Delete(
     *     path="/api/comments/delete/{commentId}",
     *     tags={"Comments"},
     *     summary="Delete a comment",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="commentId",
     *         in="path",
     *         required=true,
     *         description="ID of the comment to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Comment deleted successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Comment not found")
     * )
     */

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