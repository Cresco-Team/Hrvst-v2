<?php

namespace App\Models\Interaction;

use App\Models\Marketplace\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reaction extends Model
{
    protected $fillable = [
        'user_id',
        'post_id',
        'type',
    ];

    /* ---------- relationships ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /* ---------- accessors ---------- */

    public function getEmojiAttribute(): string
    {
        return match($this->type) {
            'like'  => '👍',
            'love'  => '❤️',
            'wheat' => '🌾',
            'deal'  => '🤝',
            default => '👍',
        };
    }
}
