<?php

namespace App\Models\Address;

use App\Models\Profiles\FarmerProfile;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['municipality_id', 'name'])]
class Barangay extends Model
{
    public $timestamps = false;

    /* ---------- relations ---------- */

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function farmers(): HasMany
    {
        return $this->hasMany(FarmerProfile::class);
    }
}
