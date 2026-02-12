<?php

namespace App\Models\Product;

use App\Models\Announcement\AnnouncementComment;
use App\Models\Announcement\AnnouncementFlag;
use App\Models\Messaging\Conversation;
use App\Models\Profiles\FarmerProfile;
use App\PlantingStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Planting extends Model
{
    protected $fillable = [
        'farmer_id',
        'variety_id',
        'weight_kg',
        'asking_price',
        'expiration_date',
        'image_path',
        'status'
    ];

    protected $casts = [
        'expiration_date' => 'date',
        'status' => PlantingStatus::class,
    ];

    /* ---------- relations ---------- */

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(FarmerProfile::class, 'farmer_id');
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(AnnouncementComment::class);
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(AnnouncementFlag::class, 'reactionable');
    }

    public function flags(): MorphMany
    {
        return $this->morphMany(AnnouncementFlag::class, 'flaggable');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /* ---------- predicate ---------- */

    public function isExpired(): bool
    {
        return Carbon::parse($this->expiration_date)->isPast();
    }

    /* ---------- actions ---------- */

    public function markAsArchived(?float $actualWeight = null): void
    {
        $this->update([
            'status' => PlantingStatus::Archived,
            'expiration_date' => Carbon::now(),
            'weight_kg' => $actualWeight ?? $this->weight_kg,
        ]);
    }

    /* ---------- scopes ---------- */

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', PlantingStatus::Available);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', PlantingStatus::Archived);
    }

    /* ---------- boolean check ---------- */

    public function isAvailable(): bool
    {
        return $this->status === PlantingStatus::Available;
    }

    public function isArchived(): bool
    {
        return $this->status === PlantingStatus::Archived;
    }


    /* ---------- accessors ---------- */
    public function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path
                ? asset('storage/' . $this->image_path)
                : null
        );
    }

    public function daysUntilExpiration(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->status !== PlantingStatus::Available) {
                    return null;
                }

                return Carbon::now()->diffInDays($this->expiration_date, false);
            }
        );
    }

    public function varietyName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->variety()
            ? "{$this->variety->vegetable->name} {$this->variety->name}"
            : 'Unknown'
        );
    }

    public function reactionCounts(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->reactions
                ->groupBy('reaction_type')
                ->map(fn ($group) => $group->count())
                ->toArray()
        );
    }
}
