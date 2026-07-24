<?php

namespace App\Models;

use App\Enums\RegistrationRequestStatus;
use App\Enums\ValidIdType;
use App\Models\Address\Barangay;
use App\Models\Address\Municipality;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'name', 'phone_number', 'email', 'role', 'pin',
    'municipality_id', 'barangay_id', 'latitude', 'longitude',
    'id_type', 'id_number',
    'status', 'reviewed_by', 'reviewed_at', 'rejection_reason',
])]
class RegistrationRequest extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected function casts(): array
    {
        return [
            'pin' => 'hashed',
            'id_type' => ValidIdType::class,
            'status' => RegistrationRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('supporting_document')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);
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
