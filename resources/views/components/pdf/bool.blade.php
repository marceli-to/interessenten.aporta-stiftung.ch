{{-- Ja/Nein-Zelle: "Ja" hervorgehoben, "Nein" neutral, null als Strich. --}}
@props(['value' => null])
@if(is_null($value))
  <dd class="empty">–</dd>
@elseif($value)
  <dd class="yes">Ja</dd>
@else
  <dd>Nein</dd>
@endif
