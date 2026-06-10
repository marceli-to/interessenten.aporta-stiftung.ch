@props(['type' => 'submit'])

<button
	type="{{ $type }}"
	{{ $attributes->merge(['class' => 'text-xl leading-none font-medium rounded-full inline-flex items-center justify-center gap-2 cursor-pointer transition-colors focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed px-20 py-10 bg-blue text-white hover:bg-blue/90']) }}>
	{{ $slot }}
</button>
