<x-mail::message>
# @if($purpose === 'google_signin')
Google Sign-In Verification
@else
Password Reset Code
@endif

Hello **{{ $recipientName }}**,

@if($purpose === 'google_signin')
Use the verification code below to complete your Google sign-in for **{{ \App\Services\ConfigurationService::getBusinessName() }}**.
@else
We received a request to reset your password for **{{ \App\Services\ConfigurationService::getBusinessName() }}**.
@endif

<x-mail::panel>
<div style="text-align: center; letter-spacing: 12px; font-size: 32px; font-weight: bold; font-family: monospace; color: #111827; padding: 8px 0;">
{{ $otp }}
</div>
</x-mail::panel>

This code is valid for **10 minutes**.

> **Never share this code with anyone.** {{ \App\Services\ConfigurationService::getBusinessName() }} staff will never ask for your OTP.

If you did not request this, you can safely ignore this email.

Thanks,  
{{ config('app.name') }}
</x-mail::message>