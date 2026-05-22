@props(['type' => 'text', 'name', 'id' => null, 'value' => null, 'placeholder' => null])

<input
	type="{{ $type }}"
	name="{{ $name }}"
	id="{{ $id ?? $name }}"
	@if($value) value="{{ $value }}" @endif
	@if($placeholder) placeholder="{{ $placeholder }}" @endif
	{{ $attributes->merge([
		'class' => 'block w-full px-12 py-10 border border-gray-200 dark:border-warm-700 rounded-md bg-white dark:bg-warm-800 text-sm dark:text-warm-100 dark:placeholder:text-warm-600 transition-all focus:outline-none focus:ring-2 focus:ring-gray-200 dark:focus:ring-warm-700 focus:border-gray-300 dark:focus:border-warm-600'
	]) }}
/>
