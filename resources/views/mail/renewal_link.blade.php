<x-mail::message>
Guten Tag {{ $name }}

Vielen Dank für Ihre Kontaktaufnahme. Hiermit erhalten Sie den Link zu Ihrer Wohnungsbewerbung bei der Dr. Stephan à Porta-Stiftung.

Bitte aktualisieren und bestätigen Sie Ihre Angaben. Ihre Bewerbung wird damit um weitere 6 Monate verlängert.

<x-mail::button :url="$url" align="left">
Hier geht es zu Ihrer Wohnungsbewerbung
</x-mail::button>

@include('mail.partials.signature')
</x-mail::message>
