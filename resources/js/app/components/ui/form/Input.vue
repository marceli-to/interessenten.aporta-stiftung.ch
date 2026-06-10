<script setup>
import { ref, computed } from 'vue'

const model = defineModel()

const props = defineProps({
	type: { type: String, default: 'text' },
	placeholder: { type: String, default: null },
	id: { type: String, default: null },
	hasError: { type: Boolean, default: false },
	variant: {
		type: String,
		default: 'filled',
		validator: (v) => ['filled', 'outline'].includes(v),
	},
})

// `filled` is the soft fill used inside editable panels; `outline` is the
// bordered treatment used in the list filter bar.
const variants = {
	filled: 'bg-light-blue/40',
	outline: 'border border-blue bg-light-blue rounded-xs placeholder:text-blue/50',
}

// Native date inputs ignore `placeholder`. When one is given, render the field
// as text while it's empty and unfocused (which does show the placeholder) and
// swap to the real date control on focus so the picker still works. Without a
// placeholder, leave it as a native date input (keeps the browser format hint).
const focused = ref(false)
const resolvedType = computed(() =>
	props.type === 'date' && props.placeholder && !focused.value && !model.value
		? 'text'
		: props.type
)
</script>

<template>
	<input
		:id="id"
		:type="resolvedType"
		:placeholder="placeholder"
		v-model="model"
		@focus="focused = true"
		@blur="focused = false"
		class="w-full px-5 min-h-32 ring-0! focus:ring-0! outline-none!"
		:class="hasError ? 'bg-light-red/30' : (variants[variant] ?? variants.filled)"
	/>
</template>
