<x-mail::message>
@component('mail::message')
# Hello {{ $staff->name }}

You have been scheduled on:

- **Date:** {{ $date->toFormattedDateString() }}
- **Time:** {{ $time }}

Please be on time.

Thanks,  
{{ config('app.name') }}
@endcomponent
</x-mail::message>
