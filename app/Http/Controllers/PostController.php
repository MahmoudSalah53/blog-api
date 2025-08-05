<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use App\Traits\ApiResponse;

class PostController extends Controller
{
    use ApiResponse;

    protected function normalizeTags($tags)
    {
        if (is_null($tags))
            return null;

        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $tags = $decoded;
            } else {
                if (preg_match('/^\d+(,\d+)*$/', $tags)) {
                    $tags = array_map('intval', explode(',', $tags));
                }
            }
        }

        if (is_numeric($tags)) {
            $tags = [(int) $tags];
        }

        return $tags;
    }

    public function addNewPost(Request $request)
    {
        $tags = $this->normalizeTags($request->input('tags'));
        if (!is_null($tags)) {
            $request->merge(['tags' => $tags]);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:tags,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        try {
            $post = new Post();
            $post->title = $request->title;
            $post->content = $request->content;
            $post->user_id = auth()->id();
            $post->save();

            if (!is_null($request->tags)) {
                $post->tags()->sync($request->tags);
            }

            $post->load('user', 'tags');

            for ($i = 1; $i <= 3; $i++) {
                Cache::forget("posts_page_$i");
            }

            return $this->success([
                'message' => 'Post created successfully.',
                'post' => new PostResource($post),
            ], 201);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function editPost(Request $request, $postId)
    {
        $tags = $this->normalizeTags($request->input('tags'));
        if (!is_null($tags)) {
            $request->merge(['tags' => $tags]);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:tags,id',
            'append_tags' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        $post = Post::find($postId);
        if (!$post) {
            return $this->error('Post not found.', 404);
        }

        if ($post->user_id !== auth()->id()) {
            return $this->error('Unauthorized.', 403);
        }

        try {
            $post->update([
                'title' => $request->title,
                'content' => $request->content,
            ]);

            if (!is_null($request->tags)) {
                $append = filter_var($request->boolean('append_tags'), FILTER_VALIDATE_BOOLEAN);
                if ($append) {
                    $post->tags()->syncWithoutDetaching($request->tags);
                } else {
                    $post->tags()->sync($request->tags);
                }
            }

            $post->load('user', 'tags');

            for ($i = 1; $i <= 3; $i++) {
                Cache::forget("posts_page_$i");
            }

            Cache::forget("post_$postId");

            return $this->success([
                'message' => 'Post updated successfully.',
                'post' => new PostResource($post),
            ], 200);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function attachTags(Request $request, $postId)
    {
        $tags = $this->normalizeTags($request->input('tags'));
        if (is_null($tags)) {
            return $this->error(['tags' => ['The tags field is required.']], 422);
        }
        $request->merge(['tags' => $tags]);

        $validator = Validator::make($request->all(), [
            'tags' => 'required|array',
            'tags.*' => 'integer|exists:tags,id',
        ]);
        if ($validator->fails())
            return $this->error($validator->errors(), 422);

        $post = Post::find($postId);
        if (!$post)
            return $this->error('Post not found.', 404);
        if ($post->user_id !== auth()->id())
            return $this->error('Unauthorized.', 403);

        $post->tags()->syncWithoutDetaching($request->tags);
        $post->load('tags');

        Cache::forget("post_$postId");

        return $this->success(['message' => 'Tags attached.', 'post' => new PostResource($post)], 200);
    }

    public function detachTags(Request $request, $postId)
    {
        $tags = $this->normalizeTags($request->input('tags'));
        if (is_null($tags)) {
            return $this->error(['tags' => ['The tags field is required.']], 422);
        }
        $request->merge(['tags' => $tags]);

        $validator = Validator::make($request->all(), [
            'tags' => 'required|array',
            'tags.*' => 'integer|exists:tags,id',
        ]);
        if ($validator->fails())
            return $this->error($validator->errors(), 422);

        $post = Post::find($postId);
        if (!$post)
            return $this->error('Post not found.', 404);
        if ($post->user_id !== auth()->id())
            return $this->error('Unauthorized.', 403);

        $post->tags()->detach($request->tags);
        $post->load('tags');

        Cache::forget("post_$postId");

        return $this->success(['message' => 'Tags detached.', 'post' => new PostResource($post)], 200);
    }

    public function getAllPosts()
    {
        try {

            $page = request()->get('page', 1);
            $cacheKey = "posts_page_$page";

            $posts = cache()->remember($cacheKey, 60, function () use ($page) {
                return Post::with('user', 'comment.user', 'tags')
                    ->withCount('like')
                    ->orderBy('created_at', 'DESC')
                    ->paginate(3, ['*'], 'page', $page);
            });

            return $this->success([
                'posts' => PostResource::collection($posts),
            ], 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function getPost($postId)
    {
        try {
            $cacheKey = "post_$postId";

            $post = cache()->remember($cacheKey, 60, function () use ($postId) {
                return Post::with('user', 'comment.user', 'tags')
                    ->withCount('like')
                    ->findOrFail($postId);
            });

            return $this->success([
                'post' => new PostResource($post),
            ], 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function deletePost($postId)
    {
        $post = Post::find($postId);
        if (!$post)
            return $this->error('Post not found.', 404);
        if ($post->user_id !== auth()->id())
            return $this->error('Unauthorized.', 403);

        try {
            $post->delete();

            for ($i = 1; $i <= 3; $i++) {
                Cache::forget("posts_page_$i");
            }

            Cache::forget("post_$postId");

            return $this->success(['message' => 'Post deleted successfully.'], 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}