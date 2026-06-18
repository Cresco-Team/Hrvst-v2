<?php

namespace App\Models\Address;

use App\Models\Profiles\FarmerProfile;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['province_id', 'name', 'latitude', 'longitude'])]

class Municipality extends Model
{
    public $timestamps = false;

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
