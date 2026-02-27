<?php

namespace App\Models\Marketplace;

use App\Models\Marketplace\Post;
use App\Models\Profiles\DealerProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class DealerDemand extends Model
{
    protected $fillable = [
        'dealer_id',
        'transaction_date',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
        ];
    }

    protected $with = ['post'];

    /* ---------- relationships ---------- */

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(DealerProfile::class, 'dealer_id');
    }

    public function post(): MorphOne
    {
        return $this->morphOne(Post::class, 'postable');
    }
}
