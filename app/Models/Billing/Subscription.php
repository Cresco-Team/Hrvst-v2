<?php

namespace App\Models\Billing;

use App\Enums\Billing\SubscriptionFeature;
use App\Enums\Billing\SubscriptionPlan;
use App\Enums\Billing\SubscriptionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'feature', 'plan', 'status',
    'amount_cents', 'currency', 'payment_gateway', 'payment_reference',
    'starts_at', 'ends_at', 'cancelled_at',
])]
class Subscription extends Model
{
    protected function casts(): array
    {
        return [
            'feature' => SubscriptionFeature::class,
            'plan' => SubscriptionPlan::class,
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', SubscriptionStatus::Expired)
            ->where('ends_at', '>', now());
    }

    public function scopeFor(Builder $query, User $user, SubscriptionFeature $feature): Builder
    {
        return $query->where('user_id', $user->id)->where('feature', $feature);
    }

    public static function currentFor(User $user, SubscriptionFeature $feature): ?self
    {
        return static::query()->for($user, $feature)->latest('created_at')->first();
    }

    public static function hasAccess(User $user, SubscriptionFeature $feature): bool
    {
        return static::active()->for($user, $feature)->exists();
    }
}
