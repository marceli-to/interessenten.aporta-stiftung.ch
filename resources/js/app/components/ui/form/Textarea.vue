<script setup>
const model = defineModel()

defineProps({
	placeholder: { type: String, default: null },
	id: { type: String, default: null },
	rows: { type: [String, Number], default: 3 },
	hasError: { type: Boolean, default: false },
	variant: {
		type: String,
		default: 'filled',
		validator: (v) => ['filled', 'outline', 'white'].includes(v),
	},
})

// `filled` is the soft fill used inside editable panels; `outline` is the
// bordered treatment used in the list filter bar; `white` is the new-note field
// that pops against the yellow Notizen panel.
const variants = {
	filled: 'bg-light-blue/40',
	outline: 'border border-blue bg-light-blue rounded-xs placeholder:text-blue/50',
	white: 'bg-white rounded-lg p-10!',
}
</script>

<template>
	<textarea
		:id="id"
		:rows="rows"
		:placeholder="placeholder"
		v-model="model"
		class="w-full px-5 py-5 ring-0! focus:ring-0! outline-none! field-sizing-content min-h-100"
		:class="variants[variant] ?? variants.filled"
	/>
</template>
