{{--
  PDF-Vorschau einer EINZELNEN Wohnungsbewerbung (Dr. Stephan à Porta-Stiftung).

  Wird nur von der Dev-Route /dev/pdf-vorschau gerendert (mit _preview_data.php).
  Der echte Export rendert pdf.applications (Mehrfach) — beide teilen sich
  _styles.blade.php und _application.blade.php, damit das Layout nicht driftet.

  Datenform $a: bereits aufgelöste Anzeige-Labels (Enum->label(), formatierte
  Daten/Beträge), siehe App\Actions\Application\Pdf\Present.
--}}
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Interessent Nr. {{ $a['reference_number'] }}</title>
@include('pdf._styles')
</head>
<body>
@include('pdf._application', ['a' => $a, 'generatedAt' => $generatedAt])
</body>
</html>
