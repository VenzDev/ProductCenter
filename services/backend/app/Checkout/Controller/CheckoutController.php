<?php

declare(strict_types=1);

namespace App\Checkout\Controller;

use App\Checkout\Request\CheckoutRequest;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Payment\Gateway\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function store(CheckoutRequest $request, PaymentGateway $gateway): JsonResponse
    {
        /** @var array<int, array{product_id: int, quantity: int}> $itemsInput */
        $itemsInput = $request->validated('items');
        $items = collect($itemsInput);

        /** @var Collection<int, Product> $products */
        $products = Product::query()
            ->whereIn('id', $items->pluck('product_id'))
            ->get()
            ->keyBy('id');

        if ($products->pluck('currency')->unique()->count() > 1) {
            return response()->json(['message' => 'Cart items must share a single currency.'], 422);
        }

        $totalCents = $items->sum(function (array $item) use ($products) {
            /** @var Product $product */
            $product = $products->get($item['product_id']);

            return $product->price_cents * $item['quantity'];
        });

        /** @var Product $firstProduct */
        $firstProduct = $products->first();
        $currency = $firstProduct->currency;

        /** @var User $user */
        $user = $request->user();

        [$order, $intent] = DB::transaction(function () use ($user, $items, $products, $totalCents, $currency, $gateway) {
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total_cents' => $totalCents,
                'currency' => $currency,
            ]);

            foreach ($items as $item) {
                /** @var Product $product */
                $product = $products->get($item['product_id']);

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price_cents' => $product->price_cents,
                ]);
            }

            $intent = $gateway->createPaymentIntent($totalCents, $currency, [
                'order_id' => (string) $order->id,
            ]);

            $order->update(['payment_reference' => $intent->id]);

            return [$order, $intent];
        });

        return response()->json([
            'order_id' => $order->id,
            'client_secret' => $intent->clientSecret,
        ]);
    }
}
