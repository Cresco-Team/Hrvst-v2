<?php

namespace App\DTOs\Dealer;

use App\Enums\Analytics\RecommendationSeverity;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class DealerDashboardRecommendationDTO
{
    public function __construct(
        public readonly RecommendationSeverity $severity,
        public readonly string $type,
        public readonly string $title,
        public readonly string $body,
    ) {}

    public function toArray(): array
    {
        return [
            'severity' => $this->severity,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
        ];
    }
}
