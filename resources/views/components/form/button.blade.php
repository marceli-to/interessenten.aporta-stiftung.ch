@props(['type' => 'submit', 'variant' => 'primary'])

@php
$base = 'text-sm inline-flex items-center justify-center gap-2 rounded-md cursor-pointer transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:focus:ring-warm-600 dark:focus:ring-offset-warm-900 disabled:opacity-50 disabled:cursor-not-allowed px-16 py-8';
$variants = [
	'primary' => 'bg-gray-900 dark:bg-warm-100 text-white dark:text-warm-900 hover:bg-gray-800 dark:hover:bg-warm-200',
	'secondary' => 'bg-gray-100 dark:bg-warm-800 text-gray-700 dark:text-warm-300 hover:bg-gray-200 dark:hover:bg-warm-700',
];
@endphp

<button
	type="{{ $type }}"
	{{ $attributes->merge(['class' => $base . ' ' . ($variants[$variant] ?? $variants['primary'])]) }}
>
	{{ $slot }}
</button>
