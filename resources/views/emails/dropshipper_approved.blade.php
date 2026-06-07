<!DOCTYPE html>
<html>
<head>
    <title>Account Approved</title>
</head>
<body>
    <h1>Hello, {{ $user->first_name }}!</h1>
    <p>We are pleased to inform you that your dropshipper account has been approved by the admin.</p>
    <p>You can now log in to your account and start using our dropshipping features.</p>
    <p>Login here: <a href="{{ config('app.frontend_url') }}/login">{{ config('app.frontend_url') }}/login</a></p>
    <p>Thank you for choosing us!</p>
    <p>Best regards,<br>{{ config('app.name') }} Team</p>
</body>
</html>
