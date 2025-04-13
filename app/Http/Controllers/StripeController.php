<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderViewResource;
use App\Mail\CheckoutCompleted;
use App\Mail\NewOrderMail;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use App\OrderStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Stripe\Account;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeController extends Controller
{
    public function success(Request $request)
    {
        $user = Auth::user();
        $session_id = $request->get('session_id');
        $orders = Order::where('stripe_session_id', $session_id)->get();

        if ($orders->count() === 0) {
            abort(400);
        }
        foreach ($orders as $order) {
            if ($order->user_id !== $user->id) {
                abort(403);
            }
        }

        return Inertia::render('Stripe/Success', [
            'orders' => OrderViewResource::collection($orders)->collection->toArray()
        ]);
    }

    public function failure() {}

    public function webhook(Request $request)
    {
        // Initialize Stripe client using secret key from config
        $stripe = new StripeClient(config('app.stripe_secret_key'));

        $endpoint_secret = config('app.stripe_webhook_secret');

        // Retirieve the raw request payload (JSON string)
        $payload = $request->getContent();

        // Get signature header sent by stripe
        $sig_header = request()->header('Stripe-Signature');
        $event = null;

        try {
            // Verify the webhook signature and construct the event
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error($e);
            return response('invalid Payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error($e);
            return response('invalid Signature', 400);
        }

        // Log the incoming event type for debugging
        Log::info('==========================');
        Log::info('==========================');
        Log::info($event);
        Log::info($event->type);

        switch ($event->type) {
            // Handle updated charge (typically when the payment is settled)
            case 'charge.updated':
                $charge = $event->data->object;
                $transactionId = $charge['balance_transaction'];
                $paymentIntent = $charge['payment_intent'];
                // Retrieve detailed balance transaction data
                $balanceTransaction = $stripe->balanceTransactions->retrieve($transactionId);

                // Find all orders linked to this payment intent
                $orders = Order::where('payment_intent', $paymentIntent)->get();
                $totalAmount = $balanceTransaction['amount'];
                $stripeFee = 0;
                // Extract Stripe fee amount
                foreach ($balanceTransaction['fee_details'] as $fee_detail) {
                    if ($fee_detail['type'] === 'stripe_fee') {
                        $stripeFee = $fee_detail['amount'];
                    }
                }
                $platformFeePercent = config('app.platform_fee_pct');
                // Calculate commissions and vendor subtotal for each order
                foreach ($orders as $order) {
                    $vendorShare = $order->total_price / $totalAmount;
                    $order->online_payment_commission = $vendorShare * $stripeFee;
                    $order->website_commission = ($order->total_price - $order->online_payment_commission) / 100;
                    $order->vendor_subtotal = $order->total_price - $order->online_payment_commission - $order->website_commission;
                    $order->save();

                    Mail::to($order->vendorUser)->send(new NewOrderMail($order));
                }
                if ($orders->isEmpty()) {
                    Log::warning("No orders found for payment_intent: $paymentIntent");
                } else {
                    Mail::to($orders[0]->user)->send(new CheckoutCompleted($orders));
                }
                break;
            case 'checkout.session.completed':
                $session = $event->data->object;
                $pi = $session['payment_intent'];

                // Find orders linked to this checkout session
                $orders = Order::query()
                    ->with(['orderItems']) // Eager load order items
                    ->where(['stripe_session_id' => $session['id']])
                    ->get();

                $productsToDeletedFromCart = [];
                foreach ($orders as $order) {
                    // Mark order as paid and save the payment intent
                    $order->payment_intent = $pi;
                    $order->status = OrderStatusEnum::Paid;
                    $order->save();

                    // track product IDs to remove from cart
                    $productsToDeletedFromCart = [
                        ...$productsToDeletedFromCart,
                        ...$order->orderItems->map(fn($item) => $item->product_id)->toArray()
                    ];

                    // Reduce inventory quantity based on order
                    foreach ($order->orderItems as $orderItem) {
                        $options = $orderItem->variation_type_option_ids;
                        $product = $orderItem->product;

                        if ($options) {
                            sort($options);
                            // find the correct product variation
                            $variation = $product->variations()
                                ->where('variation_type_option_ids', $options)
                                ->first();

                            if ($variation && $variation->quantity != null) {
                                $variation->quantity -= $orderItem->quantity;
                                $variation->save();
                            }
                        } else if ($product->quantity != null) {
                            $product->quantity -= $orderItem->quantity;
                            $product->save();
                        }
                    }
                }
                // Remove purchased items from the user's cart
                CartItem::query()
                    ->where('user_id', $order->user_id)
                    ->whereIn('product_id', $productsToDeletedFromCart)
                    ->where('saved_for_later', false)
                    ->delete();

                break;
            default:
                echo 'Recieved unknown event type' . $event->type;
                break;
        }
    }

    public function connect()
    {
        /**
         * @var User $user
         */
        $user = Auth::user();
        if (! $user->stripe_account_id) {
            $user->createStripeAccount(); // Default is 'express'
        }

        if (! $user->stripe_account_active) {
            return redirect($user->getStripeAccountLink()->url);
        }

        return redirect('/');
    }

    public function callback()
    {
        Stripe::setApiKey(config('app.stripe_secret_key'));

        /**
         * @var User $user
         */
        $user = Auth::user();

        $account = Account::retrieve($user->stripe_account_id);

        if ($account->charges_enabled) {
            $user->stripe_account_active = true;
            $user->save();
        }

        return redirect('/');
    }
}
