<x-mail::message>
<h1 style="text-align: center; font-size: 24px">
    🎉 Congratulations: You have a new order!
</h1>

<x-mail::button :url="url('/dashboard/vendor/orders/' . $order->id)">
    View Order Details
</x-mail::button>

<h3 style="font-size: 20px; margin: 15px">Order Summary</h3>

<x-mail::table>
    <table>
        <tbody>
            <tr>
                <td><strong>Order #</strong></td>
                <td>#{{ $order->id }}</td>
            </tr>
            <tr>
                <td><strong>Order Date</strong></td>
                <td>{{ $order->created_at->format('d M Y - H:i') }}</td>
            </tr>
            <tr>
                <td><strong>Order Total</strong></td>
                <td>{{ Number::currency($order->total_price) }}</td>
            </tr>
            <tr>
                <td><strong>Payment Processing Fee</strong></td>
                <td>{{ Number::currency($order->online_payment_commission ?? 0) }}</td>
            </tr>
            <tr>
                <td><strong>Platform Fee</strong></td>
                <td>{{ Number::currency($order->website_commission ?? 0) }}</td>
            </tr>
            <tr>
                <td><strong>Your Earnings</strong></td>
                <td>{{ Number::currency($order->vendor_subtotal ?? 0) }}</td>
            </tr>
        </tbody>
    </table>
</x-mail::table>

<hr style="margin: 30px 0">

<x-mail::table>
    <table>
        <thead>
            <tr>
                <th style="text-align: left">Item</th>
                <th>Quantity</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->orderItems as $orderItem)
                <tr>
                    <td>
                        <div style="display: flex; align-items: center">
                            <img src="{{ $orderItem->product->getImageForOptions($orderItem->variation_type_option_ids) }}"
                                    alt="Product Image"
                                    style="width: 60px; height: auto; margin-right: 10px">
                            <span style="font-size: 13px">{{ $orderItem->product->title }}</span>
                        </div>
                    </td>
                    <td style="text-align: center">{{ $orderItem->quantity }}</td>
                    <td>{{ Number::currency($orderItem->price) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-mail::table>

<x-mail::panel>
    Thank you for doing business with us.
</x-mail::panel>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
