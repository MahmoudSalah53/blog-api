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

    /**
     * @OA\Post(
     *     path="/api/posts/add",
     *     tags={"Posts"},
     *     summary="Add new post",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","content","tags"},
     *             @OA\Property(property="title", type="string", example="My Post"),
     *             @OA\Property(property="content", type="string", example="Post content"),
     *             @OA\Property(
     *                 property="tags",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1, 2, 3}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Post created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */

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

    /**
     * @OA\Post(
     *     path="/api/posts/edit/{postId}",
     *     summary="Edit post",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="postId",
     *         in="path",
     *         required=true,
     *         description="ID of the post to edit",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated title"),
     *             @OA\Property(property="content", type="string", example="Updated content"),
     *             @OA\Property(
     *                 property="tags",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={2, 3}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post updated successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Post not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */


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

    /**
     * @OA\Get(
     *     path="/api/posts/all",
     *     tags={"Posts"},
     *     summary="Get all posts",
     *     description="Retrieve a paginated list of posts. If no parameters are provided, returns all posts with default pagination.",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination (optional)",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search keyword for post title or content (optional)",
     *         required=false,
     *         @OA\Schema(type="string", example="Laravel")
     *     ),
     *     @OA\Parameter(
     *         name="tag_id",
     *         in="query",
     *         description="Filter posts by tag ID (optional)",
     *         required=false,
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of posts retrieved successfully"
     *     ),
     *     @OA\Response(response=500, description="Server error")
     * )
     */

    public function getAllPosts(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $search = $request->get('search');
            $tagId = $request->get('tag_id');

            $cacheKey = "posts_page_{$page}_search_" . md5($search . '_' . $tagId);

            $posts = cache()->remember($cacheKey, 60, function () use ($page, $search, $tagId) {
                $query = Post::with('user', 'comment.user', 'tags')
                    ->withCount('like')
                    ->orderBy('created_at', 'DESC');

                if (!empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'LIKE', "%{$search}%")
                            ->orWhere('content', 'LIKE', "%{$search}%");
                    });
                }

                if (!empty($tagId)) {
                    $query->whereHas('tags', function ($q) use ($tagId) {
                        $q->where('tags.id', $tagId);
                    });
                }

                return $query->paginate(3, ['*'], 'page', $page);
            });

            return $this->success([
                'posts' => PostResource::collection($posts),
            ], 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/posts/show/{postId}",
     *     tags={"Posts"},
     *     summary="Get a single post",
     *     description="Retrieve detailed information for a single post by its ID.",
     *     @OA\Parameter(
     *         name="postId",
     *         in="path",
     *         description="ID of the post",
     *         required=true,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post retrieved successfully"
     *     ),
     *     @OA\Response(response=404, description="Post not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */

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

    /**
     * @OA\Delete(
     *     path="/api/posts/delete/{postId}",
     *     summary="Delete post",
     *     tags={"Posts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="postId",
     *         in="path",
     *         required=true,
     *         description="ID of the post to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Post deleted successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="Post not found")
     * )
     */

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