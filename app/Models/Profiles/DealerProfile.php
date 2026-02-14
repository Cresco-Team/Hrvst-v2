<?php

namespace App\Models\Profiles;

use App\Models\Marketplace\DealerRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DealerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'is_approved',
        'document_image',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
        ];
    }

    /* ---------- relations ---------- */

    // User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Marketplace
    public function requests(): HasMany
    {
        return $this->hasMany(DealerRequest::class, 'dealer_id');
    }
}
