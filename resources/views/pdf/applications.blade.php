{{--
  PDF-Export einer Auswahl von Wohnungsbewerbungen — eine Bewerbung pro
  Deckblatt, jede beginnt auf einer neuen Seite.

  Erwartet:
   - $applications: Liste aufgelöster Anzeige-Daten (App\Actions\Application\Pdf\Present)
   - $fonts: eingebettete Schriften (App\Actions\Application\Pdf\Assets::fonts())
   - $generatedAt: Carbon-Zeitpunkt der Erzeugung (Seitenkopf)

  Seitenzahlen kommen aus dem Browsershot-Footer (pdf.footer), gesetzt in
  App\Actions\Application\Pdf\Generate.
--}}
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Interessent</title>
@include('pdf._styles')
</head>
<body>
@foreach($applications as $i => $a)
  @if($i > 0)
    <div style="page-break-before: always;"></div>
  @endif
  @include('pdf._application', ['a' => $a, 'generatedAt' => $generatedAt])
@endforeach
</body>
</html>
