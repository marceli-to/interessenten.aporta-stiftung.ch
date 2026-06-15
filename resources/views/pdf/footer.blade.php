{{--
  Browsershot-/Chrome-Footer (gerendert in den unteren Seitenrand auf JEDER
  Seite). Eigener, isolierter Kontext: er erbt NICHT das CSS der Seite, daher
  muss die Segment-Schrift hier nochmals als @font-face eingebettet werden,
  sonst fällt Chrome auf eine System-Schrift zurück. Die Klassen "pageNumber"
  und "totalPages" füllt Chrome zur Laufzeit mit der echten Seitenzahl.

  Eingebunden via ->footerView('pdf.footer', ['fonts' => …]) in
  App\Actions\Application\Pdf\Generate.
--}}
<style>
  @font-face {
    font-family: 'Segment';
    font-weight: 400;
    font-style: normal;
    src: url('{{ $fonts['regular'] }}') format('woff2');
  }
</style>
<div style="width:100%; padding:0 15mm; font-family:'Segment', Arial, sans-serif; font-size:7pt; color:#9aa0a6; -webkit-print-color-adjust:exact;">
  <table style="width:100%; border:0;">
    <tr>
      <td style="text-align:left;">Dr. Stephan à Porta-Stiftung</td>
      <td style="text-align:right;">Seite <span class="pageNumber"></span> / <span class="totalPages"></span></td>
    </tr>
  </table>
</div>
