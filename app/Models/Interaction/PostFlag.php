<?php

namespace App\Models\Interaction;

use App\Models\Marketplace\Post;
use App\Models\User;
use App\PostFlagStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PostFlag extends Model
{
    protected $fillable = [
        'user_id',
        'post_id',
        'reason',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostFlagStatus::class,
        ];
    }

    /* ---------- relationships ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
