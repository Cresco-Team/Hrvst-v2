<?php

namespace App\Models\Profiles;

use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable('user_id')]
class DealerProfile extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /* ---------- relations ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'user_id', 'user_id')
            ->where('type', PostType::Demand);
    }

    /* ---------- media ---------- */

    public function registerMediaCollections(): void
    {
        // 'documents' disk is private (storage/app/private/documents).
        // Never expose these URLs directly in API responses.
        // Serve through: GET /admin/dealers/{dealer}/document
        // guarded by the 'admin' middleware and a temporary signed URL.
        $this->addMediaCollection('document')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useDisk('documents');
    }
}
