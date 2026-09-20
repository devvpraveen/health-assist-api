<?php

namespace App\Contracts\Billing;

use App\Services\Billing\PaymentIntent;
use App\Services\Billing\PaymentResult;
use App\Services\Billing\RefundIntent;

interface PaymentGatewayInterface
{
    public function charge(PaymentIntent $intent): PaymentResult;

    public function refund(RefundIntent $intent): PaymentResult;
}
