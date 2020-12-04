@component('mail::message')
# Hi {{$user->name}}

Please add verification code into app given below


@component('mail::panel')
    verification code : {{$code}}
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
