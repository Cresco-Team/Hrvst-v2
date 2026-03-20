<?php

namespace App\Models\Interaction;

use App\Models\Product\Variety;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VarietyHeart extends Model
{
    public $timestamps = false;

    #[Fillable(['user_id', 'variety_id'])]

    /* ---------- relationships ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
    }
}
