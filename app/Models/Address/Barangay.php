<?php

namespace App\Models\Address;

use App\Models\Profiles\FarmerProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barangay extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    /* ---------- relations ---------- */

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function farmers (): HasMany
    {
        return $this->hasMany(FarmerProfile::class);
    }
}
