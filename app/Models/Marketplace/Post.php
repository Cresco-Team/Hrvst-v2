<?php

namespace App\Models\Marketplace;

use App\Enums\PostStatus;
use App\Enums\PostTimeSlot;
use App\Enums\PostType;
use App\Models\Interaction\PostHeart;
use App\Models\Product\Vegetable;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'vegetable_id',
        'type',
        'status',
        'target_month',
        'scheduled_date',
        'time_slot',
        'estimated_total_weight',
    ];

    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'status' => PostStatus::class,
            'time_slot' => PostTimeSlot::class,
            // target_month stored as varchar(7) 'YYYY-MM' — no date cast
            'scheduled_date' => 'date',
            'estimated_total_weight' => 'decimal:2',
        ];
    }

    /* ---------- relationships ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vegetable(): BelongsTo
    {
        return $this->belongsTo(Vegetable::class);
    }

    public function postItems(): HasMany
    {
        return $this->hasMany(PostItem::class);
    }

    public function hearts(): HasMany
    {
        return $this->hasMany(PostHeart::class);
    }

    public function farmerProfile(): HasOneThrough
    {
        return $this->hasOneThrough(
            FarmerProfile::class,
            User::class,
            'id',
            'user_id',
            'user_id',
            'id',
        );
    }

    public function dealerProfile(): HasOneThrough
    {
        return $this->hasOneThrough(
            DealerProfile::class,
            User::class,
            'id',
            'user_id',
            'user_id',
            'id',
        );
    }

    /* ---------- scopes ---------- */

    public function scopeSupply(Builder $query): Builder
    {
        return $query->where('type', PostType::Supply);
    }

    public function scopeDemand(Builder $query): Builder
    {
        return $query->where('type', PostType::Demand);
    }

    public function scopeGrowing(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Growing);
    }

    public function scopeHarvested(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Harvested);
    }

    /* ---------- lifecycle ---------- */

    public function markAsHarvested(string $scheduledDate): void
    {
        $this->status = PostStatus::Harvested;
        $this->scheduled_date = $scheduledDate;
        $this->save();
    }

    /* ---------- helpers ---------- */

    public function isGrowing(): bool
    {
        return $this->status === PostStatus::Growing;
    }

    /* ---------- Bug #8 fix: cascade-delete PostItems through Eloquent ----------
     * DB-level cascadeOnDelete bypasses observers, so vegetable_monthly_stats
     * never gets decremented when a Post is force-deleted.
     * Deleting PostItems explicitly here fires PostItemObserver::deleted on each.
     */

    protected static function booted(): void
    {
        static::deleting(function (Post $post): void {
            // Only needed on force-delete (soft-delete does not cascade at DB level).
            // For soft-delete, PostItems remain and can be individually managed.
            if (! $post->isForceDeleting()) {
                return;
            }

            $post->postItems()->each(fn (PostItem $item) => $item->delete());
        });
    }

    /* ---------- media ---------- */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('post_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(400)
            ->nonQueued();
    }
}
