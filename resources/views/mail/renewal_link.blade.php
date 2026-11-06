<x-mail::message>
Guten Tag {{ $name }}

Vielen Dank für Ihre Kontaktaufnahme. Wie besprochen erhalten Sie hier den Link zu Ihrer Wohnungsbewerbung (Nr. {{ $application->reference_number }}) bei der Dr. Stephan à Porta-Stiftung.

Bitte prüfen Sie Ihre Angaben und bestätigen Sie sie oder passen Sie sie an. Ihre Bewerbung wird damit um weitere 6 Monate verlängert.

<x-mail::button :url="$confirmUrl" align="left">
Angaben bestätigen
</x-mail::button>

<x-mail::button :url="$updateUrl" align="left">
Angaben aktualisieren
</x-mail::button>

Suchen Sie keine Wohnung mehr? Dann können Sie Ihre Bewerbung hier löschen: [Bewerbung löschen]({{ $deleteUrl }})

Freundliche Grüsse

Dr. Stephan à Porta-Stiftung<br>
Kreuzstrasse 31<br>
8008 Zürich
</x-mail::message>
