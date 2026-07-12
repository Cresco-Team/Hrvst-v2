<?php

namespace App\Services\Billing;

use App\Contracts\Billing\PaymentGateway;
use App\DTOs\Billing\PaymentResult;
use Illuminate\Support\Str;

final class MockPaymentGateway implements PaymentGateway
{
    public function charge(int $amountCents, string $currency, array $meta = []): PaymentResult
    {
        return new PaymentResult(successful: true, gateway: 'mock', reference: 'mock_'.Str::uuid());
    }
}
