<?php

namespace App\Models\Messaging;

use App\Models\Product\Planting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [];

    /* ---------- relations ---------- */

    public function planting(): BelongsTo
    {
        return $this->belongsTo(Planting::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->using(ConversationParticipant::class)
            ->withPivot(['last_read_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasMany
    {
        return $this->messages()->latest();
    }

    public function getOtherParticipant(int $userId): ?User
    {
        return $this->participants()
            ->where('user_id', '!=', $userId)
            ->first();
    }

    public function hasParticipant(int $userId): bool
    {
        return $this->participants()
            ->where('user_id', $userId)
            ->exists();
    }
}
