<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\VariationTypeOption;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CartService
{
    private ?array $cashedCartItems = null;
    protected const COOKIE_NAME = 'cartItems';
    protected const COOKIE_LIFETIME = 60 * 24 * 360;
    public function addItemToCart(Product $product, int $quantity = 1, $optionIds = null)
    {
        if (!$optionIds) {
            $optionIds = $product->getFirstOptionsMap();
        }
        $price = $product->getPriceForOptions($optionIds);

        if (Auth::check()) {
            $this->saveItemToDatabase($product->id, $quantity, $price, $optionIds);
        } else {
            $this->saveItemToCookies($product->id, $quantity, $price, $optionIds);
        }
    }

    public function updateItemQuantity($productId, $quantity, $optionIds = null)
    {
        if (Auth::check()) {
            $this->updateItemQuantityInDatabase($productId, $quantity, $optionIds);
        } else {
            $this->updateItemQuantityInCookies($productId, $quantity, $optionIds);
        }
    }

    public function removeItemFromCart($productId, $optionIds = null)
    {
        if (Auth::check()) {
            $this->removeItemFromDatabase($productId, $optionIds);
        } else {
            $this->removeItemFromCookies($productId, $optionIds);
        }
    }

    public function getCartItems()
    {
        try {
            if ($this->cashedCartItems === null) {
                if (Auth::check()) {
                    // if the user is authenticated,retrieve data from database
                    $cartItems = $this->getCartItemsFromDatabase();
                } else {
                    // Otherwise retrieve data from cookies
                    $cartItems = $this->getCartItemsFromCookies();
                }
                $productIds = collect($cartItems)->map(fn($item) => $item['product_id']);
                $products = Product::whereIn('id', $productIds)
                    ->with('user.vendor')
                    ->forWebsite()
                    ->get()
                    ->keyBy('id');

                $cartItemData = [];
                foreach ($cartItems as $key => $cartItem) {
                    $product = data_get($products, $cartItem['product_id']);
                    if (!$product) continue;

                    $optionInfo = [];
                    $optionIds = is_array($cartItem['option_ids']) ? $cartItem['option_ids'] : [];

                    $options = VariationTypeOption::with('variationType')
                        ->whereIn('id', $optionIds)
                        ->get()
                        ->keyBy('id');


                    $imageUrl = null;
                    foreach ($cartItem['option_ids'] as $option_id) {
                        $option = data_get($options, $option_id);
                        if (!$imageUrl) {
                            $imageUrl = $option->getFirstMediaUrl('images', 'small');
                        }
                        $optionInfo[] = [
                            'id' => $option->id,
                            'name' => $option->name,
                            'type' => [
                                'id' => $option->variationType->id,
                                'name' => $option->variationType->name
                            ],
                        ];
                    }

                    $cartItemData[] = [
                        'id' => $cartItem['id'],
                        'product_id' => $product->id,
                        'title' => $product->title,
                        'slug' => $product->slug,
                        'price' => $cartItem['price'],
                        'quantity' => $cartItem['quantity'],
                        'option_ids' => $cartItem['option_ids'],
                        'options' => $optionInfo,
                        'image' => $imageUrl ?: $product->getFirstMediaUrl('images', 'small'),
                        'user' => [
                            'id' => $product->created_by,
                            'name' => $product->user->vendor->store_name
                        ],
                    ];
                }
                $this->cashedCartItems = $cartItemData;
            }

            return $this->cashedCartItems;
        } catch (\Exception $e) {
            Log::error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
        return [];
    }

    public function getTotalQuantity()
    {
        $totalQuantity = 0;
        foreach ($this->getCartItems()  as $item) {
            $totalQuantity += $item['quantity'];
        }
        return $totalQuantity;
    }

    public function getTotalPrice()
    {
        $totalPrice = 0;
        foreach ($this->getCartItems() as $item) {
            $totalPrice += $item['price'] * $item['quantity'];
        }
        return $totalPrice;
    }

    protected function updateItemQuantityInDatabase($productId, $quantity, $optionIds)
    {
        $userId = Auth::id();
        $cartItem = CartItem::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('variation_type_option_ids', json_encode($optionIds))
            ->first();

        if ($cartItem) {
            $cartItem->update([
                'quantity' => $quantity
            ]);
        }
    }
    protected function updateItemQuantityInCookies($productId, $quantity, $optionIds)
    {
        $cartItems = $this->getCartItemsFromCookies();

        ksort($optionIds);
        // Generate a unique key based on product ID and option ids
        $itemKey = $productId . '_' . json_encode($optionIds);

        if (isset($cartItems[$itemKey])) {
            $cartItems[$itemKey]['quantity'] = $quantity;
        }

        // Save updated cart items back to the cookies
        Cookie::queue(self::COOKIE_NAME, json_encode($cartItems), self::COOKIE_LIFETIME);
    }

    public function saveItemToDatabase($productId, $quantity, $price, $optionIds)
    {
        $userId = Auth::id();

        $cartItem = CartItem::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('variation_type_option_ids', $optionIds)
            ->first();

        if ($cartItem) {
            $cartItem->update([
                'quantity' => DB::raw('quantity + ' . $quantity)
            ]);
        } else {
            CartItem::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'variation_type_option_ids' => $optionIds
            ]);
        }
    }

    public function saveItemToCookies($productId, $quantity, $price, $optionIds)
    {
        $cartItems = $this->getCartItemsFromCookies();

        ksort($optionIds);

        // Generate a unique key based on product ID and option ids
        $itemKey = $productId . '_' . json_encode($optionIds);
        if (isset($cartItems[$itemKey])) {
            $cartItems[$itemKey]['quantity'] += $quantity;
            $cartItems[$itemKey]['price'] = $price;
        } else {
            $cartItems[$itemKey] = [
                'id' => Str::uuid(),
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'option_ids' => $optionIds
            ];
        }

        // Save updated cart items back to the cookies
        Cookie::queue(self::COOKIE_NAME, json_encode($cartItems), self::COOKIE_LIFETIME);
    }

    public function removeItemFromDatabase($productId, $optionIds)
    {
        $userId = Auth::id();
        ksort($optionIds);
        CartItem::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('variation_type_option_ids', json_encode($optionIds))
            ->delete();
    }

    public function removeItemFromCookies($productId, $optionIds)
    {
        $cartItems = $this->getCartItemsFromCookies();

        ksort($optionIds);
        // Define the cart item key
        $itemKey = $productId . '_' . json_encode($optionIds);

        // Remove item from the cart cookies
        unset($cartItems[$itemKey]);

        Cookie::queue(self::COOKIE_NAME, json_encode($cartItems), self::COOKIE_LIFETIME);
    }
    public function getCartItemsFromDatabase()
    {
        $userId = Auth::id();

        $cartItems = CartItem::where('user_id', $userId)
            ->get()
            ->map(function ($cartItem) {
                return [
                    'id' => $cartItem->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'option_ids' => $cartItem->variation_type_option_ids
                ];
            })->toArray();
        return $cartItems;
    }

    public function getCartItemsFromCookies()
    {
        $cartItems = json_decode(Cookie::get(self::COOKIE_NAME, '[]'), true);
        return $cartItems;
    }

    public function getCartItemsGrouped()
    {
        $cartItems = $this->getCartItems();

        return collect($cartItems)
            ->groupBy(fn($item) => $item['user']['id'])
            ->map(fn($items, $userId) => [
                'user' => $items->first()['user'],
                'items' => $items->toArray(),
                'total_quantity' => $items->sum('quantity'),
                'total_price' => $items->sum(fn($item) => $item['quantity'] * $item['price'])
            ])->toArray();
    }

    public function moveCartItemsToDatabase($userId)
    {
        // Get the cart items from cookies   
        $cartItems = $this->getCartItemsFromCookies();
        // Loop through the cart items and insert them into the database
        foreach ($cartItems as $itemKey => $cartItem) {
            // If the cart item already exists for the user from database
            $existingItem = CartItem::where('product_id', $cartItem['product_id'])
                ->where('user_id', $userId)
                ->where('variation_type_option_ids', $cartItem['option_ids'])
                ->first();
            if ($existingItem) {
                // If the item exists, then update the quantity
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $cartItem['quantity']
                ]);
            } else {
                CartItem::create([
                    'user_id' => $userId,
                    'product_id' => $cartItem['product_id'],
                    'quantity' => $cartItem['quantity'],
                    'price' => $cartItem['price'],
                    'variation_type_option_ids' => $cartItem['option_ids']
                ]);
            }
        }
        // After transferring the items, delete the cart from cookies
        Cookie::queue(self::COOKIE_NAME, '', -1);
    }
}
