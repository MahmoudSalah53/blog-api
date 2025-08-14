<?php

namespace App\Http\Controllers;


use App\Events\PostLiked;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Validator;
use App\Traits\ApiResponse;

class LikeController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Post(
     *     path="/api/posts/like/{postId}",
     *     tags={"Likes"},
     *     summary="Toggle like/unlike for a post",
     *     description="If the post is already liked by the user, it will be unliked. Otherwise, it will be liked.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="postId",
     *         in="path",
     *         required=true,
     *         description="ID of the post to like/unlike",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=201, description="Post successfully liked/unliked"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Post not found")
     * )
     */

    public function toggleLikePost($postId)
    {
        try {
            $existingLike = Like::where('post_id', $postId)
                ->where('user_id', auth()->id())
                ->first();

            if ($existingLike) {
                $existingLike->delete();
                Cache::forget("post_$postId");
                return $this->success('Post has been successfully unliked.', 201);
            }


            $like = new Like();
            $like->post_id = $postId;
            $like->user_id = auth()->user()->id;
            $like->save();

            broadcast(new PostLiked($like))->toOthers();

            Cache::forget("post_$postId");

            return $this->success('Post successfully liked', 201);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
