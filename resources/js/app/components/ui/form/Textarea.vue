<script setup>
const model = defineModel()

defineProps({
	placeholder: { type: String, default: null },
	id: { type: String, default: null },
	rows: { type: [String, Number], default: 3 },
	hasError: { type: Boolean, default: false },
	variant: {
		type: String,
		default: 'default',
		validator: (v) => ['default', 'filter'].includes(v),
	},
})

// `default` sits inside an editable panel; `filter` is the outlined treatment
// for the (not yet built) list filter bar.
const variants = {
	default: 'bg-light-blue/40',
	filter: 'border border-blue bg-light-blue',
}
</script>

<template>
	<textarea
		:id="id"
		:rows="rows"
		:placeholder="placeholder"
		v-model="model"
		class="w-full px-5 py-5 ring-0! focus:ring-0! outline-none!"
		:class="variants[variant] ?? variants.default"
	/>
</template>
