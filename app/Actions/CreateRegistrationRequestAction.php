<?php

namespace App\Actions;

use App\Enums\RegistrationRequestStatus;
use App\Models\RegistrationRequest;
use Illuminate\Http\UploadedFile;

final class CreateRegistrationRequestAction
{
    public function handle(array $validated, ?UploadedFile $supportingDocument = null): RegistrationRequest
    {
        $registrationRequest = RegistrationRequest::create([
            'name' => $validated['name'],
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'] ?? null,
            'role' => $validated['role'],
            'pin' => $validated['pin'],
            'municipality_id' => $validated['municipality_id'] ?? null,
            'barangay_id' => $validated['barangay_id'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'id_type' => $validated['id_type'] ?? null,
            'id_number' => $validated['id_number'] ?? null,
            'status' => RegistrationRequestStatus::Pending,
        ]);

        if ($supportingDocument) {
            $registrationRequest->addMedia($supportingDocument)->toMediaCollection('supporting_document');
        }

        return $registrationRequest;
    }
}
