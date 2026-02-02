<?php

namespace App\Models\Profiles;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'is_approved',
        'document_image',
    ];

    /* ---------- relations ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
