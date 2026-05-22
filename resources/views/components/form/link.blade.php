@props(['href'])

<a
	href="{{ $href }}"
	{{ $attributes->merge(['class' => 'text-sm text-gray-400 dark:text-warm-500 hover:text-gray-900 dark:hover:text-warm-100 transition-colors underline decoration-gray-300 dark:decoration-warm-700 underline-offset-4 hover:decoration-gray-900 dark:hover:decoration-warm-400']) }}
>
	{{ $slot }}
</a>
