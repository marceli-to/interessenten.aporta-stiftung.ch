@props(['type' => 'submit', 'variant' => 'primary', 'href' => null])

@php
	$variants = [
		'primary' => 'border border-blue bg-blue text-white hover:bg-blue/90',
		'outline' => 'border border-blue text-blue hover:bg-light-blue',
	];
	$classes = 'text-xl leading-none font-medium rounded-full inline-flex items-center justify-center gap-2 cursor-pointer transition-colors focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed px-20 py-10 '.$variants[$variant];
@endphp

@if ($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
	{{ $slot }}
</a>
@else
<button
	type="{{ $type }}"
	{{ $attributes->merge(['class' => $classes]) }}>
	{{ $slot }}
</button>
@endif
