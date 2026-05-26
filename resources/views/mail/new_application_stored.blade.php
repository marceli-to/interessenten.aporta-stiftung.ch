<x-mail::message>
# Wohnungsbewerbung

Eine Wohnungsbewerbung mit der Nummer **{{ $application->reference_number }}** ist eingegangen.

@if($mainApplicant)
{{ trim($mainApplicant->first_name.' '.$mainApplicant->last_name) }}<br>
{{ trim(($mainApplicant->street ?? '').' '.($mainApplicant->street_number ?? '')) }}<br>
{{ trim(($mainApplicant->postal_code ?? '').' '.($mainApplicant->city ?? '')) }}
@endif

<x-mail::button :url="$backofficeUrl" align="left">
Anzeigen
</x-mail::button>

Dr. Stephan à Porta-Stiftung<br>
Kreuzstrasse 31<br>
8008 Zürich
</x-mail::message>
