<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    use ApiResponse;
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
            return $this->error($e->getMessage(), 403);
        }
    }

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
