<?php

namespace App\Services\Billing;

use App\Contracts\Billing\PaymentGatewayInterface;
use Illuminate\Support\Str;

class ManualPaymentGateway implements PaymentGatewayInterface
{
    public function charge(PaymentIntent $intent): PaymentResult
    {
        return PaymentResult::succeeded(
            gatewayReference: $intent->gatewayReference ?? 'manual_'.Str::lower(Str::random(12)),
            meta: [
                'method' => $intent->method,
                'gateway' => 'manual',
            ],
        );
    }

    public function refund(RefundIntent $intent): PaymentResult
    {
        return PaymentResult::succeeded(
            gatewayReference: $intent->gatewayReference ?? 'manual_refund_'.Str::lower(Str::random(12)),
            meta: [
                'gateway' => 'manual',
                'reason' => $intent->reason,
            ],
        );
    }
}
