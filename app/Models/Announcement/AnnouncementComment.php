<?php

namespace App\Models\Announcement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementComment extends Model
{
    protected $fillable = [
        'farmer_offering_id',
        'user_id',
        'comment',
    ];

    /* ---------- relationships ---------- */

    public function farmerOffering(): BelongsTo
    {
        return $this->belongsTo(FarmerOffering::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
