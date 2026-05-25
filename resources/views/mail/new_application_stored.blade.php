<x-mail::message>
# Neue Wohnungsanmeldung

Eine neue Wohnungsanmeldung mit der Nummer **{{ $application->reference_number }}** ist eingegangen.

@if($mainApplicant)
**{{ trim($mainApplicant->first_name.' '.$mainApplicant->last_name) }}**
{{ trim(($mainApplicant->street ?? '').' '.($mainApplicant->street_number ?? '')) }}
{{ trim(($mainApplicant->postal_code ?? '').' '.($mainApplicant->city ?? '')) }}
@endif

<x-mail::button :url="$backofficeUrl">
Im Backoffice öffnen
</x-mail::button>

Freundliche Grüsse
{{ config('app.name') }}
</x-mail::message>
