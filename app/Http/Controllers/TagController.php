<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    use ApiResponse;

    /**
     * @OA\Post(
     *     path="/api/tags/add",
     *     summary="Create a new tag",
     *     description="Creates a new tag with a unique name",
     *     tags={"Tags"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", minLength=2, maxLength=20, example="Technology")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tag created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="string", example="Tag created successfully."),
     *                 @OA\Property(property="tag", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Technology")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */

    public function addTag(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:20|unique:tags',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 422);
        }

        try {
            $tag = new Tag();
            $tag->name = $request->name;
            $tag->save();

            return $this->success([
                'message' => 'Tag created successfully.',
                'tag' => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ]
            ], 201);

        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/tags/delete/{tagId}'",
     *     summary="Delete a tag",
     *     description="Deletes a tag by ID",
     *     tags={"Tags"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="tagId",
     *         in="path",
     *         required=true,
     *         description="ID of the tag to delete",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tag deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message", type="string", example="Tag Deleted successfully.")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tag not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */

    public function deleteTag($tagId)
    {
        $tag = Tag::find($tagId);

        if (!$tag) {
            return $this->error('Tag not found.', 404);
        }

        try {
            $tag->delete();

            return $this->success(['message' => 'Tag Deleted successfully.'], 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
