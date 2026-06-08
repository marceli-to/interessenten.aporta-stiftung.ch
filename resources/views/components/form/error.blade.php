@props(['name'])

@foreach ($errors->get($name) as $message)
	<p {{ $attributes->merge(['class' => 'mt-5 text-sm text-red font-medium']) }}>{{ $message }}</p>
@endforeach
