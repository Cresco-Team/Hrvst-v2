<?php

namespace App\Models\Announcement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AnnouncementFlag extends Model
{
    protected $fillable = [
        'user_id',
        'flaggable_id',
        'flaggable_type',
        'reason',
        'description',
        'status',
    ];

    /* ---------- relationships ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function flaggable(): MorphTo
    {
        return $this->morphTo();
    }
}
