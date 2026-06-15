{{-- Wert-Zelle: zeigt den Wert oder einen dezenten Strich, wenn leer.
     Arrays (z.B. Kreise/Zimmer) werden komma-separiert ausgegeben. --}}
@props(['value' => null])
@php
  $display = is_array($value) ? implode(', ', $value) : $value;
  $isEmpty = $display === null || $display === '' || $display === [];
@endphp
<dd class="{{ $isEmpty ? 'empty' : '' }}">{{ $isEmpty ? '–' : $display }}</dd>
