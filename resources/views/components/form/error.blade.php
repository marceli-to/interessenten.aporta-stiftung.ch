@props(['name'])

@foreach ($errors->get($name) as $message)
	<p {{ $attributes->merge(['class' => 'mt-4 text-xs text-red-600']) }}>{{ $message }}</p>
@endforeach
