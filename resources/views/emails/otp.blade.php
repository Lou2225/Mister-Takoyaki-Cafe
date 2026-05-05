<x-mail::message>
# Password Reset Code

Hello **{{ $recipientName }}**,

We received a request to reset your password for the **Mister Takoyaki Management System**.

Use the code below to verify your identity. This code is valid for **10 minutes**.

<x-mail::panel>
<div style="text-align: center; letter-spacing: 12px; font-size: 32px; font-weight: bold; font-family: monospace; color: #111827; padding: 8px 0;">
{{ $otp }}
</div>
</x-mail::panel>

> **Never share this code with anyone.** Mister Takoyaki staff will never ask for your OTP.
> If you did not request a password reset, you can safely ignore this email — your account remains secure.

Thanks,
{{ config('app.name') }} Management
</x-mail::message>
