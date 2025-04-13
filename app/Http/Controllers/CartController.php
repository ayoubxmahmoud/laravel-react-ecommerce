<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\OrderStatusEnum;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class CartController extends Controller
{
    /**
     * Display the user's cart items
     */
    public function index(CartService $cartService)
    {
        return Inertia::render('Cart/Index', [
            'cartItems' => $cartService->getCartItemsGrouped()
        ]);
    }

    /**
     * Add a product to the cart
     */
    public function store(Request $request, Product $product, CartService $cartService)
    {
        // If no quantity is passed, default to 1
        $request->mergeIfMissing([
            'quantity' => 1
        ]);

        // Validate input data
        $data = $request->validate([
            'option_ids' => ['nullable', 'array'] ?: [],
            'quantity' => ['required', 'integer', 'min:1']
        ]);
        $cartService->addItemToCart($product, $data['quantity'], $data['option_ids']);

        return back()->with('success', 'Product added to cart successfully!');
    }


    /**
     * Update quantity of a specific product in the cart.
     */
    public function update(Request $request, Product $product, CartService $cartService)
    {
        $request->validate([
            'quantity' => ['integer', 'min:1']
        ]);

        $optionIds = $request->input('option_ids') ?: [];
        $quantity = $request->input('quantity');
        $cartService->updateItemQuantity($product->id, $quantity, $optionIds);

        return back()->with('success', 'Quantity was updated');
    }

    /**
    * Remove a product item from the cart.
    */
    public function destroy(Request $request, Product $product, CartService $cartService)
    {
        $optionIds = $request->input('option_ids');
        $cartService->removeItemFromCart($product->id, $optionIds);

        return back()->with('success', 'Product was removed from cart');
    }

    // Handle Stripe checkout process.
    public function checkout(Request $request, CartService $cartService)
    {
        // Set Stripe secret key for API usage.
        Stripe::setApiKey(config('app.stripe_secret_key'));

        $vendorId = $request->input('vendor_id');

        $allCartItems = $cartService->getCartItemsGrouped();
        DB::beginTransaction();
        try {
            // Filter cart items for a specific vendor if vendor_id is passed.
            $checkoutCartItems = $allCartItems;
            if ($vendorId) {
                $checkoutCartItems = [$allCartItems[$vendorId]];
            }
            $orders = [];
            $lineItems = [];

            // Loop through cart items and create orders and order_items
            foreach ($checkoutCartItems as $item) {
                $user = $item['user'];
                $cartItems = $item['items'];

                // Create order
                $order = Order::create([
                    'stripe_session_id' => null,
                    'user_id' => $request->user()->id,
                    'vendor_user_id' => $user['id'],
                    'total_price' => $item['total_price'],
                    'status' => OrderStatusEnum::Draft->value
                ]);
                $orders[] = $order;

                // Create order_items and line_items
                foreach ($cartItems as $cartItem) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $cartItem['product_id'],
                        'price' => $cartItem['price'],
                        'quantity' => $cartItem['quantity'],
                        'variation_type_option_ids' => $cartItem['option_ids']
                    ]);

                    // Format product description from selected options
                    $description = collect($cartItem['options'])->map(function ($option) {
                        return "{$option['type']['name']}: {$option['name']}";
                    })->implode(', ');

                    // Prepare line item for Stripe checkout
                    $lineItem = [
                        'price_data' => [
                            'currency' => config('app.currency'),
                            'product_data' => [
                                'name' => $cartItem['title'],
                                'images' => [$cartItem['image']]
                            ],
                            'unit_amount' => $cartItem['price'] * 100,// Stripe requires amount in cents
                        ],
                        'quantity' => $cartItem['quantity']
                    ];
                    // Attach description if it exists
                    if ($description) {
                        $lineItem['price_data']['product_data']['description'] = $description;
                    }
                    $lineItems[] = $lineItem;
                }

            }

            // Create Stripe checkout session
            $session = Session::create([
                'customer_email' => $request->user()->email,
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => route('stripe.success', []) . "?session_id={CHECKOUT_SESSION_ID}",
                'cancel_url' => route('stripe.failure', []),
            ]);
            // Save Stripe session ID to orders
            foreach ($orders as $order) {
                $order->stripe_session_id = $session->id;
                $order->save();
            }
            DB::commit();
            // Redirect to stripe checkout page
            return redirect($session->url);
        } catch (\Exception $e) {
            // Handle and log any errors
            Log::error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
            DB::rollBack();
            return back()->with('error', $e->getMessage() ?: 'Something went wrong');
        }
    }
}
