{{-- Inline styles for the application PDF (pdf.applications / _application).
     Needs $fonts. --}}
<style>
  /* --- Schrift (Segment, eingebettet als Base64 für stabiles PDF-Rendering) --- */
  @font-face {
    font-family: 'Segment';
    font-weight: 400;
    font-style: normal;
    src: url('{{ $fonts['regular'] }}') format('woff2');
  }
  @font-face {
    font-family: 'Segment';
    font-weight: 500;
    font-style: normal;
    src: url('{{ $fonts['medium'] }}') format('woff2');
  }
  @font-face {
    font-family: 'Segment';
    font-weight: 700;
    font-style: normal;
    src: url('{{ $fonts['bold'] }}') format('woff2');
  }

  :root {
    --blue: #194164;
    --ink: #2f2f2f;
    --muted: #6b7280;
    --faint: #b8bcc2;
    --bar: #eceff3;
    --hairline: #e4e7eb;
  }

  /* Bottom margin leaves room for the Browsershot footer (page numbers); the
     Pdf\Generate action sets the same margins on the renderer. */
  @page {
    size: A4;
    margin: 12mm 15mm 16mm 15mm;
  }

  * { box-sizing: border-box; }
  html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

  body {
    font-family: 'Segment', system-ui, sans-serif;
    color: var(--ink);
    font-size: 9.5pt;
    line-height: 1.45;
    margin: 0;
  }

  /* Browser preview only: frame the flow like a page (print uses @page margins). */
  @media screen {
    body {
      max-width: 210mm;
      margin: 0 auto;
      padding: 12mm 15mm;
    }
  }

  /* --- Wiederholter Seitenkopf ---
     Über thead einer Layout-Tabelle: Chrome/Browsershot wiederholt diesen
     zuverlässig auf jeder Druckseite, ohne Overlap und ohne Rand-Rechnerei.
     Der Fuss (Seitenzahlen) kommt aus dem Browsershot-Footer (pdf.footer). */
  table.layout { width: 100%; border-collapse: collapse; }
  table.layout > thead .head-cell { padding: 0 0 16px; }
  table.layout > tbody .body-cell { padding: 0; }

  .run-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    font-size: 7.5pt;
    font-weight: 400;
    color: var(--muted);
    padding-bottom: 12px;
  }
  .run-head .doc-title {
    font-size: 9pt;
    flex: 1;
  }
  .run-head .ref  { white-space: nowrap; }
  .run-head .when { white-space: nowrap; text-align: right; }

  /* --- Deckblatt --- */
  .cover {
    page-break-after: always;
    text-align: center;
    padding-top: 38mm;
  }
  .cover .title {
    font-size: 18pt;
    font-weight: 700;
    color: var(--blue);
    letter-spacing: -0.01em;
    margin: 0;
  }
  .cover .org {
    font-size: 9.5pt;
    color: var(--muted);
    margin-top: 6px;
  }
  /* Zwei gleich breite Spalten, Container per margin:auto zentriert → die
     Trennlinie Label/Wert sitzt exakt in der Seitenmitte. */
  .cover-summary {
    width: 130mm;
    margin: 40mm auto 0;
  }
  .cover-summary .pair {
    display: flex;
    padding: 2px 0;
  }
  .cover-summary dt {
    flex: 1;
    text-align: right;
    padding-right: 9px;
    color: var(--muted);
  }
  .cover-summary dd {
    flex: 1;
    margin: 0;
    text-align: left;
    padding-left: 9px;
    color: var(--ink);
  }

  /* --- Abschnitte --- */
  .block { page-break-inside: avoid; margin-bottom: 28px; }
  .bar {
    background: var(--bar);
    color: var(--blue);
    font-weight: 700;
    font-size: 9pt;
    padding: 5px;
    margin-bottom: 0;
  }

  dl { margin: 0; }
  .row {
    display: flex;
    align-items: baseline;
    gap: 14px;
    padding: 5px;
    border-bottom: 1px solid var(--hairline);
  }
  .row dt {
    width: 42mm;
    flex: none;
    color: var(--muted);
    font-weight: 400;
  }
  .row dd {
    flex: 1;
    margin: 0;
    color: var(--ink);
  }
  .row dd.empty { color: var(--faint); font-weight: 400; }
  .row dd.long { font-weight: 400; }

</style>
