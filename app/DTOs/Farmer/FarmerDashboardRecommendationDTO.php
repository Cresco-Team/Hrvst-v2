<?php

namespace App\DTOs\Farmer;

final class FarmerDashboardRecommendationDTO
{
    public function __construct(
        public readonly string $severity,
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
