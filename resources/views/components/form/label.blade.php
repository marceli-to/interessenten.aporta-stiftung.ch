@props(['for' => null])

<label
	@if($for) for="{{ $for }}" @endif
	{{ $attributes->merge(['class' => 'block text-sm text-blue mb-5']) }}
>
	{{ $slot }}
</label>
