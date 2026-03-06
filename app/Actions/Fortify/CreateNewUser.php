<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function create(array $input): User
    {
        $this->validateInput($input);

        return DB::transaction(function () use ($input): User {
            $user = $this->createUser($input);

            $this->assignRole($user, $input['role']);

            match ($input['role']) {
                'farmer' => $this->createFarmerProfile($user, $input),
                'dealer' => $this->createDealerProfile($user, $input),
            };

            return $user;
        });
    }

    private function validateInput(array $input): void
    {
        $role = $input['role'] ?? null;

        $rules = [
            'role'          => ['required', 'string', Rule::in(['farmer', 'dealer'])],
            ...$this->profileRules(),
            'phone_number'  => $this->phoneNumberRules(),
            'password'      => $this->passwordRules(),
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];

        if ($role === 'farmer') {
            $rules += [
                'province_id'     => ['required', 'integer', Rule::exists('provinces', 'id')],
                'municipality_id' => ['required', 'integer', Rule::exists('municipalities', 'id')],
                'barangay_id'     => ['required', 'integer', Rule::exists('barangays', 'id')],
                'latitude'        => ['required', 'numeric', 'between:-90,90'],
                'longitude'       => ['required', 'numeric', 'between:-180,180'],
                'farm_image'      => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            ];
        }

        if ($role === 'dealer') {
            $rules['document_image'] = ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'];
        }

        Validator::make($input, $rules, [
            'phone_number.regex'          => 'Phone number must be a valid PH mobile number (e.g. 09171234567).',
            'phone_number.unique'         => 'This phone number is already registered.',
            'farm_image.required'         => 'A farm photo is required as proof of your farm.',
            'document_image.required'     => 'A document photo is required for dealer verification.',
        ])->validate();
    }

    private function createUser(array $input): User
    {
        $user = User::create([
            'name'         => $input['name'],
            'email'        => $input['email'],
            'password'     => $input['password'],
            'phone_number' => $input['phone_number'],
        ]);

        if (isset($input['profile_image']) && $input['profile_image'] instanceof UploadedFile) {
            $user->addMedia($input['profile_image'])->toMediaCollection('avatar');
        }

        return $user;
    }

    private function assignRole(User $user, string $roleName): void
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user->roles()->attach($role);
    }

    private function createFarmerProfile(User $user, array $input): void
    {
        $farmer = FarmerProfile::create([
            'user_id'         => $user->id,
            'province_id'     => $input['province_id'],
            'municipality_id' => $input['municipality_id'],
            'barangay_id'     => $input['barangay_id'],
            'latitude'        => $input['latitude'],
            'longitude'       => $input['longitude'],
        ]);

        /** @var UploadedFile $farmImage */
        $farmImage = $input['farm_image'];
        $farmer->addMedia($farmImage)->toMediaCollection('farm_photo');
    }

    private function createDealerProfile(User $user, array $input): void
    {
        $dealer = DealerProfile::create(['user_id' => $user->id]);

        /** @var UploadedFile $documentImage */
        $documentImage = $input['document_image'];

        // Stored on the private 'documents' disk — never served by a public URL.
        // Add GET /admin/dealers/{dealer}/document guarded by 'admin' middleware
        // that calls $dealer->getFirstMedia('document')->toResponse($request)
        $dealer->addMedia($documentImage)->toMediaCollection('document');
    }
}
