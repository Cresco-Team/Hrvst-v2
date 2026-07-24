<?php

namespace App\Data\RegistrationRequest;

use App\Enums\ValidIdType;
use App\Models\RegistrationRequest;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class RegistrationRequestData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $phone_number,
        public ?string $email,
        public string $role,
        public string $created_at,
        public ?string $municipality,
        public ?string $barangay,
        public ?string $id_type,
        public ?string $id_type_label,
        public ?string $id_number,
        public ?string $document_url,
    ) {}

    public static function fromModel(RegistrationRequest $request): self
    {
        $idType = $request->id_type instanceof ValidIdType ? $request->id_type : null;
        $documentUrl = $request->getFirstMediaUrl('supporting_document');

        return new self(
            id: $request->id,
            name: $request->name,
            phone_number: $request->phone_number,
            email: $request->email,
            role: $request->role,
            created_at: $request->created_at->toIso8601String(),
            municipality: $request->municipality?->name,
            barangay: $request->barangay?->name,
            id_type: $idType?->value,
            id_type_label: $idType?->label(),
            id_number: $request->id_number,
            document_url: $documentUrl !== '' ? $documentUrl : null,
        );
    }
}
