<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Due Invoice</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; line-height: 1.5; }
        .container { width: 100%; margin: 0 auto; }
        .header { border-bottom: 2px solid #3b82f6; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { color: #3b82f6; margin: 0; font-size: 28px; }
        .invoice-info { float: right; text-align: right; }
        .user-info { margin-bottom: 30px; }
        .user-info h3 { margin: 0 0 10px 0; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f9fafb; border-bottom: 1px solid #e5e7eb; padding: 12px; text-align: left; font-size: 12px; text-transform: uppercase; color: #6b7280; }
        td { border-bottom: 1px solid #f3f4f6; padding: 12px; font-size: 14px; }
        .total-section { float: right; width: 250px; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; }
        .total-row.final { border-top: 2px solid #e5e7eb; margin-top: 10px; padding-top: 10px; font-weight: bold; font-size: 18px; color: #111; }
        .footer { position: fixed; bottom: 30px; left: 0; width: 100%; text-align: center; font-size: 10px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="invoice-info">
                <p><strong>Invoice No:</strong> {{ $invoice_no }}</p>
                <p><strong>Date:</strong> {{ $date }}</p>
            </div>
            <h1>DUE INVOICE</h1>
            <p>ValoKichu.com - Dropshipping Network</p>
        </div>

        <div class="user-info">
            <h3>Bill To:</h3>
            <p><strong>{{ $user->first_name }} {{ $user->last_name }}</strong></p>
            <p>{{ $user->email }}</p>
            <p>{{ $user->phone_number }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Order #</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Base Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($details as $item)
                <tr>
                    <td>{{ $item['date'] }}</td>
                    <td>{{ $item['order_number'] }}</td>
                    <td>{{ $item['product_name'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>৳{{ number_format($item['base_price'], 2) }}</td>
                    <td style="text-align: right;">৳{{ number_format($item['line_total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-row final">
                <span>Total Due:</span>
                <span style="float: right;">৳{{ number_format($total_due, 2) }}</span>
            </div>
        </div>

        <div class="footer">
            <p>Thank you for being a valued partner of ValoKichu.com</p>
            <p>This is a computer-generated invoice and does not require a signature.</p>
        </div>
    </div>
</body>
</html>
