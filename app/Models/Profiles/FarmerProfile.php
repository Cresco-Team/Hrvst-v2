<?php

namespace App\Models\Profiles;

use App\Models\Product\Planting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FarmerProfile extends Model
{
    use HasFactory;

    protected $fillable = [];

    /* ---------- relations ---------- */

    public function plantings(): HasMany
    {
        return $this->hasMany(Planting::class);
    }
}
