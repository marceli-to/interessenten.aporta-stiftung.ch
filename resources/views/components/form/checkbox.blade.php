@props(['name', 'id' => null, 'checked' => false])

<label class="inline-flex items-center gap-2.5 cursor-pointer group">
	<span class="relative flex items-center justify-center size-3.5">
		<input
			type="checkbox"
			name="{{ $name }}"
			id="{{ $id ?? $name }}"
			@checked($checked)
			{{ $attributes->merge([
				'class' => 'peer appearance-none size-3.5 border border-gray-200 rounded bg-white checked:bg-gray-900 checked:border-gray-900 focus:ring-2 focus:ring-gray-200 focus:ring-offset-0 cursor-pointer transition-colors'
			]) }}
		/>
		<svg
			class="absolute pointer-events-none text-white opacity-0 peer-checked:opacity-100 transition-opacity"
			width="10" height="10" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
		>
			<polyline points="1.5 5.5 4 8 8.5 2.5" />
		</svg>
	</span>
	<span class="text-sm text-gray-500 group-hover:text-gray-900 transition-colors select-none">{{ $slot }}</span>
</label>
