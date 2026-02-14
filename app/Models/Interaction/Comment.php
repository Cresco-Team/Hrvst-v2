<?php

namespace App\Models\Interaction;

use App\Models\Marketplace\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Comment extends Model
{
    protected $fillable = [
        'farmer_offering_id',
        'user_id',
        'post_id',
        'parent_id',
        'body',
    ];

    /* ---------- relationships ---------- */

    // User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Marketplace
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function reactions(): MorphMany
    {   // Can have reactions
        return $this->morphMnay(Reaction::class, 'reactable');
    }
}
