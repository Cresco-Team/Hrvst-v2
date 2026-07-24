<?php

namespace App\Enums;

enum ValidIdType: string
{
    case DriversLicense = 'drivers_license';
    case PhilippineNationalId = 'philippine_national_id';
    case PhilippinePassport = 'philippine_passport';
    case VotersId = 'voters_id';

    public function label(): string
    {
        return match ($this) {
            self::DriversLicense => "Driver's License",
            self::PhilippineNationalId => 'Philippine National ID (PhilSys)',
            self::PhilippinePassport => 'Philippine Passport',
            self::VotersId => "Voter's ID",
        };
    }

    /**
     * Best-effort formats from public documentation — not verified against
     * an official LTO/PSA/DFA/COMELEC spec. Confirm before treating a
     * mismatch as a hard rejection reason in front of an applicant.
     */
    public function regex(): string
    {
        return match ($this) {
            self::DriversLicense => '/^[A-Z]\d{2}-\d{2}-\d{6}$/',
            self::PhilippineNationalId => '/^\d{4}-\d{4}-\d{4}$/',
            self::PhilippinePassport => '/^[A-Z]{1,2}\d{6,7}$/',
            self::VotersId => '/^\d{8,10}$/',
        };
    }

    public function formatHint(): string
    {
        return match ($this) {
            self::DriversLicense => 'Format: N01-23-456789',
            self::PhilippineNationalId => 'Format: 1234-5678-9012',
            self::PhilippinePassport => 'Format: P1234567',
            self::VotersId => '8–10 digit voter reference number',
        };
    }
}
