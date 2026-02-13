<?php

namespace App\Models\Interaction;

use App\Models\Marketplace\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reaction extends Model
{
    protected $fillable = [
        'user_id',
        'post_id',
        'type',
    ];

    /* ---------- relationships ---------- */

    // User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Marketplace
    public function reactable(): BelongsTo
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
