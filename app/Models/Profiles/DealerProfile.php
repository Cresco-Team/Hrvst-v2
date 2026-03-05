<?php

namespace App\Models\Profiles;

use App\Models\Marketplace\DealerDemand;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
    public function demands(): HasMany
    {
        return $this->hasMany(DealerDemand::class, 'dealer_id');
    }

    /* ---------- scopes ---------- */

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_approved', false);
    }

    /* ---------- actions ---------- */

    public function approveAccount(): void
    {
        $this->is_approved = true;
        $this->save();
    }

    public function rejectAccount(): void
    {
        $user = $this->user;
        $this->delete();
        $user->delete();
    }
}
