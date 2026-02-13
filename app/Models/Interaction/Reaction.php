<?php

namespace App\Models\Interaction;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reaction extends Model
{
    protected $fillable = [
        'user_id',
        'reactable_id',
        'reactable_type',
        'type',
    ];

    /* ---------- relationships ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactable(): MorphTo
    {   // Link to whatever was reacted to (Post or Comment)
        return $this->morphTo();
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
