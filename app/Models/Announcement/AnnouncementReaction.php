<?php

namespace App\Models\Announcement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AnnouncementReaction extends Model
{
    protected $fillable = [
        'user_id',
        'reactionable_id',
        'reactionable_type',
        'reaction_type',
    ];

    /* ---------- relationships ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactionable(): MorphTo
    {
        return $this->morphTo();
    }
}
