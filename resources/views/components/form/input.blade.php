@props(['type' => 'text', 'name', 'id' => null, 'value' => null, 'placeholder' => null])

<input
	type="{{ $type }}"
	name="{{ $name }}"
	id="{{ $id ?? $name }}"
	@if($value) value="{{ $value }}" @endif
	@if($placeholder) placeholder="{{ $placeholder }}" @endif
	{{ $attributes->merge([
		'class' => 'block w-full px-12 py-10 border border-gray-200 rounded-md bg-white text-sm transition-all focus:outline-none focus:ring-2 focus:ring-gray-200 focus:border-gray-300'
	]) }}
/>
