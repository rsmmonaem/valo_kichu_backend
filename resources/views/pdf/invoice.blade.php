<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; color: #333; line-height: 1.5; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); font-size: 16px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .company-info h2 { margin: 0; color: #2563eb; }
        .invoice-details { text-align: right; }
        .details-table { width: 100%; text-align: left; border-collapse: collapse; margin-top: 30px; }
        .details-table th { background-color: #f3f4f6; border: 1px solid #e5e7eb; padding: 10px; }
        .details-table td { border: 1px solid #e5e7eb; padding: 10px; }
        .totals { margin-top: 30px; text-align: right; }
        .totals p { margin: 5px 0; font-size: 18px; }
        .totals .grand-total { font-weight: bold; color: #2563eb; font-size: 22px; }
        .footer { margin-top: 50px; text-align: center; color: #6b7280; font-size: 12px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table style="width: 100%;">
            <tr>
                <td class="company-info">
                    <h2>ValoKichu</h2>
                    <p>Quality Products, Better Living</p>
                </td>
                <td class="invoice-details" style="text-align: right;">
                    <h3>INVOICE</h3>
                    <p>Order #: {{ $order->order_number }}</p>
                    <p>Date: {{ $order->created_at->format('F d, Y') }}</p>
                </td>
            </tr>
        </table>

        <div style="margin-top: 20px;">
            <strong>Bill To:</strong><br>
            {{ $order->name }}<br>
            {{ $order->email }}<br>
            {{ $order->contact_number }}<br>
            {{ $order->shipping_address }}
        </div>

        <table class="details-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if($item->variation_snapshot)
                            <br><small style="color: #666;">({{ $item->variation_snapshot }})</small>
                        @endif
                    </td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->total_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <p>Subtotal: {{ number_format($order->subtotal, 2) }} {{ $order->currency }}</p>
            <p>Shipping: {{ number_format($order->shipping_cost, 2) }} {{ $order->currency }}</p>
            @if($order->discount > 0)
                <p>Discount: -{{ number_format($order->discount, 2) }} {{ $order->currency }}</p>
            @endif
            <p class="grand-total">Total: {{ number_format($order->total_price, 2) }} {{ $order->currency }}</p>
        </div>

        <div class="footer">
            <p>Thank you for shopping with us!</p>
            <p>www.valokichu.com</p>
        </div>
    </div>
</body>
</html>
