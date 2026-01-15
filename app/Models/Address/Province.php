<?php

namespace App\Models\Address;

use App\Models\Profiles\FarmerProfile;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Province extends Model
{
    public $timestamps = false;

    /* ---------- relations ---------- */

    public function municipalities(): HasMany
    {
        return $this->hasMany(Municipality::class);
    }

    public function farmers(): HasMany
    {
        return $this->hasMany(FarmerProfile::class);
    }
}
