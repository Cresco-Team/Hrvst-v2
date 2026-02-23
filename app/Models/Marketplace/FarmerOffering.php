<?php

namespace App\Models\Marketplace;

use App\Models\Marketplace\Post;
use App\Models\Profiles\FarmerProfile;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class FarmerOffering extends Model
{
    protected $fillable = [
        'farmer_id',
        'expiration_date',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'expiration_date' => 'date',
        ];
    }

    protected $with = ['post'];

    /* ---------- relationships ---------- */

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(FarmerProfile::class, 'farmer_id');
    }

    public function post(): MorphOne
    {
        return $this->morphOne(Post::class, 'postable');
    }

    /* ---------- accessors ---------- */

    public function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path 
                ? asset('storage/supply-image/' . $this->image_path)
                : null
        );
    }
}
