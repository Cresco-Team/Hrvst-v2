<?php

namespace App\Data\Profile;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $email,
        public string $phone_number,
        public string $avatar_url,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            phone_number: $user->phone_number,
            avatar_url: $user->getFirstMediaUrl('avatar'),
        );
    }
}
