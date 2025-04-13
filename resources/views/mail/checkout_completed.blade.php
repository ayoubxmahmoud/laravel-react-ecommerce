<x-mail::message>
<h1 style="text-align: center; font-size: 24px">
    ✅ Payment was Completed Successfully
</h1>

@foreach ($orders as $order)
    <x-mail::table>
    <table>
        <tbody>
            <tr>
                <td><strong>Seller</strong></td>
                <td>
                    <a href="{{ url('/') }}" style="color: #3869D4; text-decoration: underline">
                        {{ $order->vendorUser->vendor->store_name }}
                    </a>
                </td>
            </tr>
            <tr>
                <td><strong>Order #</strong></td>
                <td>#{{ $order->id }}</td>
            </tr>
            <tr>
                <td><strong>Items</strong></td>
                <td>{{ $order->orderItems->count() }}</td>
            </tr>
            <tr>
                <td><strong>Total</strong></td>
                <td>{{ Number::currency($order->total_price) }}</td>
            </tr>
        </tbody>
    </table>
    </x-mail::table>

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
                                    alt="Product Image" style="width: 60px; height: auto; margin-right: 10px">
                                <span style="font-size: 13px">
                                    {{ $orderItem->product->title }}
                                </span>
                            </div>
                        </td>
                        <td style="text-align: center">{{ $orderItem->quantity }}</td>
                        <td>{{ Number::currency($orderItem->price) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-mail::table>

    <x-mail::button :url="url('/dashboard/orders/' . $order->id)">
        View Order Details
    </x-mail::button>
@endforeach

<x-mail::subcopy>
    If you have any questions or concerns about your order, feel free to reply to this email or contact our support
    team.
</x-mail::subcopy>

<x-mail::panel>
    Need help? Visit our <a href="https://amazon.com/support"
        style="color: #3869D4; text-decoration: underline">Support Center</a>
    or email us directly at <a href="mailto:support@amazon.com">support@amazon.com</a>.
</x-mail::panel>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>