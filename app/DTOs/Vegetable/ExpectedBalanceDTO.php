<?php

namespace App\DTOs\Vegetable;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
readonly class ExpectedBalanceDTO
{
    public function __construct(
        public string $band,
        public string $explanation,
        public ?ExpectedBalanceComputation $computation = null,
    ) {}

    public function toArray(): array
    {
        return [
            'band' => $this->band,
            'explanation' => $this->explanation,
            'computation' => $this->computation?->toArray(),
        ];
    }
}
