<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// get all posts
Route::get('/posts/all', [PostController::class, 'getAllPosts']);
// show post
Route::get('/posts/show/{postId}', [PostController::class, 'getPost']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Posts Prefix
    Route::prefix('posts')->group(function () {
        Route::post('/add', [PostController::class, 'addNewPost']);
        Route::post('/edit/{postId}', [PostController::class, 'editPost']);
        Route::post('/{postId}/like', [LikeController::class, 'toggleLikePost']);
        Route::delete('/delete/{postId}', [PostController::class, 'deletePost']);
    });

    // Comments Prefix
    Route::prefix('comments')->group(function () {
        Route::post('/add/{postId}', [CommentController::class, 'addNewComment']);
        Route::delete('/delete/{commentId}', [CommentController::class, 'deleteComment']);
    });

    // Tags Prefix
    Route::prefix('tags')->group(function () {
        Route::post('/add', [TagController::class, 'addTag']);
        Route::delete('/delete/{tagId}', [TagController::class, 'deleteTag']);
    });

});
