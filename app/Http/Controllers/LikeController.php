<?php

namespace App\Http\Controllers;


use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Validator;
use App\Traits\ApiResponse;

class LikeController extends Controller
{
    use ApiResponse;

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
            
            Cache::forget("post_$postId");

            return $this->success('Post successfully liked', 201);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
