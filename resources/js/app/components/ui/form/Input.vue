<script setup>
const model = defineModel()

defineProps({
	type: { type: String, default: 'text' },
	placeholder: { type: String, default: null },
	id: { type: String, default: null },
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
	<input
		:id="id"
		:type="type"
		:placeholder="placeholder"
		v-model="model"
		class="w-full px-5 min-h-32 ring-0! focus:ring-0! outline-none!"
		:class="variants[variant] ?? variants.default"
	/>
</template>
