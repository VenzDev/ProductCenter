<?php

declare(strict_types=1);

namespace App\Payment\Controller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Payment\Gateway\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class StripeWebhookController extends Controller
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function __invoke(Request $request): Response
    {
        try {
            $event = $this->gateway->parseWebhookEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
            );
        } catch (Throwable) {
            return response()->noContent(400);
        }

        $status = match ($event->type) {
            'payment_intent.succeeded' => 'paid',
            'payment_intent.payment_failed' => 'failed',
            default => null,
        };

        if ($status !== null) {
            // "WHERE status = 'pending'" makes this idempotent against Stripe's
            // at-least-once delivery — a duplicate event is a no-op (see docs/design.md).
            Order::query()
                ->where('payment_reference', $event->paymentIntentId)
                ->where('status', 'pending')
                ->update(['status' => $status]);
        }

        return response()->noContent();
    }
}
