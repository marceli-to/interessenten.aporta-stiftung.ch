@props(['for' => null])

<label
	@if($for) for="{{ $for }}" @endif
	{{ $attributes->merge(['class' => 'block text-xs text-accent mb-8']) }}
>
	{{ $slot }}
</label>
