<?php

namespace App\DTOs\Vegetable;

use App\Enums\Analytics\RecommendationSeverity;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
readonly class VegetableRecommendationDTO
{
    public function __construct(
        public RecommendationSeverity $severity,
        public string $type,
        public string $title,
        public string $body,
    ) {}

    public function toArray(): array
    {
        return [
            'severity' => $this->severity->value,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}
