@props(['for' => null])

<label
	@if($for) for="{{ $for }}" @endif
	{{ $attributes->merge(['class' => 'block text-xs text-blue mb-8']) }}
>
	{{ $slot }}
</label>
