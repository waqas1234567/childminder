@component('mail::message')
# Reset Password

code : {{$code}}



Thanks,<br>
{{ config('app.name') }}
@endcomponent
