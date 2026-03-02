<!DOCTYPE html>
<html>
<head>
    <title>Order Invoice</title>
</head>
<body>
    <h1>Thank you for your order!</h1>
    <p>Dear {{ $order->name }},</p>
    <p>Please find your invoice attached for order #{{ $order->order_number }}.</p>
    <p>If you have any questions, feel free to contact us.</p>
    <br>
    <p>Best Regards,</p>
    <p>ValoKichu Team</p>
</body>
</html>
