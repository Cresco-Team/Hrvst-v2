<?php

namespace App\Models\Address;

use App\Models\Profiles\FarmerProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipality extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'latitude', 'longitude'];

    /* ---------- relations ---------- */

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function barangays(): HasMany
    {
        return $this->hasMany(Barangay::class);
    }

    public function farmers(): HasMany
    {
        return $this->hasMany(FarmerProfile::class);
    }
}
