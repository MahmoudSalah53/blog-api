<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponse;


class PostController extends Controller
{
    use ApiResponse;
    public function addNewPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        try {

            $post = new Post();
            $post->title = $request->title;
            $post->content = $request->content;
            $post->user_id = auth()->user()->id;
            $post->save();

            return $this->success([
                'message' => 'Post Created successfully.',
                'post' => new PostResource($post)
            ]);


        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    // edit post 
    public function editPost(Request $request, $postId)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        $post_data = Post::find($postId);

        if ($post_data->user_id !== auth()->id()) {
            return $this->error('Unauthorized.', 403);
        }

        try {
            $updatePost = $post_data->update([
                'title' => $request->title,
                'content' => $request->content,
            ]);

            return $this->success(['message' => 'Post updated successfully.'], 200);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function getAllPosts()
    {
        try {
            $posts = Post::with('user', 'comment.user')
                ->withCount('like')
                ->paginate(10);

            return $this->success([
                'posts' => PostResource::collection($posts)
            ], 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function getPost($postId)
    {
        try {
            $post = Post::with('user', 'comment.user')->withCount('like')->findOrFail($postId);

            return $this->success([
                'post' => new PostResource($post)
            ], 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function deletePost($postId)
    {
        $post = Post::find($postId);

        if (!$post) {
            return $this->error('Post not found.', 404);
        }

        if ($post->user_id !== auth()->id()) {
            return $this->error('Unauthorized.', 403);
        }

        try {
            $post->delete();

            return $this->success(['message' => 'Post Deleted successfully.'], 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

}