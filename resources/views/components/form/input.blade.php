@props(['type' => 'text', 'name', 'id' => null, 'value' => null, 'placeholder' => null])

<input
	type="{{ $type }}"
	name="{{ $name }}"
	id="{{ $id ?? $name }}"
	@if($value) value="{{ $value }}" @endif
	@if($placeholder) placeholder="{{ $placeholder }}" @endif
	{{ $attributes->merge([
		'class' => 'block w-full px-10 py-10 border border-blue/50 rounded-md bg-white text-sm transition-all placeholder:text-blue/50 focus:outline-none focus:ring-none focus:border-blue'
	]) }}
/>
