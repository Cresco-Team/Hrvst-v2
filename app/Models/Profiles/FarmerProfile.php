<?php

namespace App\Models\Profiles;

use App\Models\Address\Barangay;
use App\Models\Address\Municipality;
use App\Models\Address\Province;
use App\Models\Marketplace\FarmerSupply;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class FarmerProfile extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'province_id',
        'municipality_id',
        'barangay_id',
        'is_approved',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
        ];
    }

    /* ---------- relations ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function supplies(): HasMany
    {
        return $this->hasMany(FarmerSupply::class, 'farmer_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    /* ---------- scopes ---------- */

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_approved', false);
    }

    /* ---------- actions ---------- */

    public function approveAccount(): void
    {
        $this->is_approved = true;
        $this->save();
    }

    public function rejectAccount(): void
    {
        $user = $this->user;
        $this->delete();
        $user->delete();
    }

    /* ---------- media ---------- */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('farm_photo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
