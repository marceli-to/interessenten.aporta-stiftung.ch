@props(['href'])

<a
	href="{{ $href }}"
	{{ $attributes->merge(['class' => 'text-sm text-accent hover:text-accent/80 transition-colors no-underline underline-offset-2 hover:underline']) }}
>
	{{ $slot }}
</a>
