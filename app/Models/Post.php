<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comment()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function like()
    {
        return $this->hasMany(Like::class, 'post_id');
    }

    public function tags()
{
    return $this->belongsToMany(Tag::class);
}
}
