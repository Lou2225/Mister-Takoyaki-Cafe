<x-mail::message>
# Welcome to {{ \App\Services\ConfigurationService::getBusinessName() }}!

Hello **{{ $user->first_name }} {{ $user->last_name }}**,

Your system account has been successfully created by the administrator. Welcome to the team!
Below are your secure auto-generated credentials to access the {{ \App\Services\ConfigurationService::getBusinessName() }} Management System.

<x-mail::panel>
**Employee ID:** `{{ $user->employee_id }}`<br>
**Password:** `{{ $plainPassword }}`
</x-mail::panel>

<x-mail::button :url="config('app.url') . '/login'" color="success">
Login to System
</x-mail::button>

> **Important:** For security reasons, please do not share your credentials with anyone. If you lose your password, contact your branch manager to reset it.

Thanks,<br>
{{ config('app.name') }} Management
</x-mail::message>

