<?php

namespace App\Models\Profiles;

use App\Models\Address\Barangay;
use App\Models\Address\Municipality;
use App\Models\Address\Province;
use App\Models\Marketplace\FarmerSupply;
use App\Models\Marketplace\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class FarmerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'province_id',
        'municipality_id',
        'barangay_id',
        'is_approved',
        'latitude',
        'longitude',
        'farm_image',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
        ];
    }

    /* ---------- relations ---------- */

    // User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Marketplace
    public function supplies(): HasMany
    {
        return $this->hasMany(FarmerSupply::class, 'farmer_id');
    }

    // Address
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

    /* ---------- accessors ---------- */

    public function farmUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->farm_image 
                ? asset('storage/' . $this->farm_image)
                : null
        );
    }
}
