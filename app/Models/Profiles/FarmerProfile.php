<?php

namespace App\Models\Profiles;

use App\Models\Address\Barangay;
use App\Models\Address\Municipality;
use App\Models\Address\Province;
use App\Models\Marketplace\FarmerOffering;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    /* ---------- relations ---------- */

    // User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Marketplace
    public function offerings(): HasMany
    {
        return $this->hasMany(FarmerOffering::class, 'farmer_id');
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
}
