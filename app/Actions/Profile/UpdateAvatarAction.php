<?php

namespace App\Actions\Profile;

use App\Models\User;
use Illuminate\Http\UploadedFile;

final class UpdateAvatarAction
{
    public function handle(User $user, UploadedFile $avatar): void
    {
        $user->addMedia($avatar)->toMediaCollection('avatar');
    }
}
