<?php

namespace App\Models;

use App\Enums\RegistrationRequestStatus;
use App\Models\Address\Barangay;
use App\Models\Address\Municipality;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name', 'phone_number', 'email', 'role', 'pin',
    'municipality_id', 'barangay_id', 'latitude', 'longitude',
    'status', 'reviewed_by', 'reviewed_at',
])]
class RegistrationRequest extends Model
{
    protected function casts(): array
    {
        return [
            'pin' => 'hashed',
            'status' => RegistrationRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
