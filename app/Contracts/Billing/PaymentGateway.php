<?php

namespace App\Contracts\Billing;

use App\DTOs\Billing\PaymentResult;

interface PaymentGateway
{
    public function charge(int $amountCents, string $currency, array $meta = []): PaymentResult;
}
