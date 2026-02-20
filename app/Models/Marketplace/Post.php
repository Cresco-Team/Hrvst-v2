<?php

namespace App\Models\Marketplace;

use App\Models\Interaction\Comment;
use App\Models\Interaction\PostFlag;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Post extends Model
{
    protected $fillable = [
        'user_id',
        'postable_id',
        'postable_type',
        'title',
    ];

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp'
    ];

    /* ---------- relationships ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function postable(): MorphTo
    {   // Magic link to FarmerOffering and DealerDemand
        return $this->morphTo();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function flags(): HasMany
    {
        return $this->hasMany(PostFlag::class);
    }
}
