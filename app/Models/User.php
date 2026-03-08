<?php

namespace App\Models;

use App\Models\Interaction\Comment;
use App\Models\Interaction\PostFlag;
use App\Models\Interaction\Reaction;
use App\Models\Marketplace\Post;
use App\Models\Messaging\Conversation;
use App\Models\Messaging\ConversationParticipant;
use App\Models\Messaging\Message;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable, InteractsWithMedia;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /* ---------- relations ---------- */

    // Profiles
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function farmerProfile(): HasOne
    {
        return $this->hasOne(FarmerProfile::class);
    }

    public function dealerProfile(): HasOne
    {
        return $this->hasOne(DealerProfile::class);
    }

    // Marketplace
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function postFlags(): HasMany
    {
        return $this->hasMany(PostFlag::class);
    }

    // Conversation
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->using(ConversationParticipant::class)
            ->withPivot(['last_read_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /* ---------- methods ---------- */

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
