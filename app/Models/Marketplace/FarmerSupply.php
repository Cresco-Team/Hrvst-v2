<?php

namespace App\Models\Marketplace;

use App\Models\Profiles\FarmerProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FarmerSupply extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'farmer_id',
        'expiration_date',
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

    /* ---------- media ---------- */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('supply_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(400)
            ->height(400)
            ->nonQueued();
    }
}
