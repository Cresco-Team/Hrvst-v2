<?php

namespace App\DTOs\Billing;

readonly class PaymentResult
{
    public function __construct(
        public bool $successful,
        public string $gateway,
        public ?string $reference = null,
        public ?string $message = null,
    ) {}
}
