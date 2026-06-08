@props(['href'])

<a
	href="{{ $href }}"
	{{ $attributes->merge(['class' => 'text-sm text-blue no-underline underline-offset-2 hover:underline']) }}
>
	{{ $slot }}
</a>
