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

        $profileImage  = $input['profile_image'] instanceof UploadedFile
            ? $input['profile_image']
            : null;

        $farmImage     = isset($input['farm_image']) && $input['farm_image'] instanceof UploadedFile
            ? $input['farm_image']
            : null;

        $documentImage = isset($input['document_image']) && $input['document_image'] instanceof UploadedFile
            ? $input['document_image']
            : null;

        return DB::transaction(function () use ($input, $profileImage, $farmImage, $documentImage): User {
            $user = $this->createUser($input, $profileImage);

            $this->assignRole($user, $input['role']);

            match ($input['role']) {
                'farmer' => $this->createFarmerProfile($user, $input, $farmImage),
                'dealer' => $this->createDealerProfile($user, $documentImage),
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

    private function createUser(array $input, ?UploadedFile $profileImage): User
    {
        $user = User::create([
            'name'         => $input['name'],
            'email'        => $input['email'],
            'password'     => $input['password'],
            'phone_number' => $input['phone_number'],
        ]);

        if ($profileImage !== null) {
            $user->addMedia($profileImage)->toMediaCollection('avatar');
        }

        return $user;
    }

    private function assignRole(User $user, string $roleName): void
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user->roles()->attach($role);
    }

    private function createFarmerProfile(User $user, array $input, ?UploadedFile $farmImage): void
    {
        $farmer = FarmerProfile::create([
            'user_id'         => $user->id,
            'province_id'     => $input['province_id'],
            'municipality_id' => $input['municipality_id'],
            'barangay_id'     => $input['barangay_id'],
            'latitude'        => $input['latitude'],
            'longitude'       => $input['longitude'],
        ]);

        if ($farmImage !== null) {
            $farmer->addMedia($farmImage)->toMediaCollection('farm_photo');
        }
    }

    private function createDealerProfile(User $user, ?UploadedFile $documentImage): void
    {
        $dealer = DealerProfile::create(['user_id' => $user->id]);

        if ($documentImage !== null) {
            $dealer->addMedia($documentImage)->toMediaCollection('document');
        }
    }
}
