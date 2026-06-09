<script setup>
// Multi-select rendered as toggleable chips. Mirrors the chip presentation used
// in read mode (StadtKreise / Stockwerke / Anzahl Zimmer). Binds to an array of
// selected slugs via v-model.
const model = defineModel({ type: Array, default: () => [] })

defineProps({
	// `{ value, label }[]`
	options: { type: Array, default: () => [] },
})

function toggle(value) {
	const next = new Set(model.value)
	next.has(value) ? next.delete(value) : next.add(value)
	model.value = [...next]
}
</script>

<template>
	<div class="flex flex-wrap gap-8">
		<button
			v-for="option in options"
			:key="option.value"
			type="button"
			class="h-30 px-12 rounded-md border text-sm transition-colors cursor-pointer"
			:class="model.includes(option.value)
				? 'border-blue bg-blue text-white'
				: 'border-blue/40 text-blue hover:bg-light-blue'"
			@click="toggle(option.value)"
		>
			{{ option.label }}
		</button>
	</div>
</template>
