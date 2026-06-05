@props(['type' => 'submit', 'variant' => 'primary'])

@php
$base = 'text-sm inline-flex items-center justify-center gap-2 rounded-md cursor-pointer transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue disabled:opacity-50 disabled:cursor-not-allowed px-16 py-8';
$variants = [
	'primary' => 'bg-blue text-white hover:bg-blue/90',
	'secondary' => 'bg-gray-100 text-gray-700 hover:bg-gray-200',
];
@endphp

<button
	type="{{ $type }}"
	{{ $attributes->merge(['class' => $base . ' ' . ($variants[$variant] ?? $variants['primary'])]) }}
>
	{{ $slot }}
</button>
