@component('mail::message')
# Hi {{$contact->name}}

Contact information

@component('mail::panel')
    Pin code : {{$code}}<br>
    Ios app url : https://play.google.com/store/apps/childminder<br>
    Android app url  : https://apps.apple.com/us/app/childminder
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
