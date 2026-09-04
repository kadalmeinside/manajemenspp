<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;

class PaymentGatewayFactory
{
    /**
     * Membuat instance layanan Payment Gateway berdasarkan pengaturan.
     *
     * @return PaymentGatewayInterface
     */
    public static function make(?string $forcedGateway = null): PaymentGatewayInterface
    {
        $activeGateway = $forcedGateway ?? config('payment.active_gateway') ?? \App\Models\Setting::where('key', 'active_payment_gateway')->value('value') ?? 'xendit';

        if (strtolower($activeGateway) === 'midtrans') {
            return new MidtransService();
        }

        if (strtolower($activeGateway) === 'gapura') {
            return new GapuraService();
        }

        // Default to Xendit
        return new XenditService();
    }
}
