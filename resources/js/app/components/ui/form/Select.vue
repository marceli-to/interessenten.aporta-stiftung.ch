<script setup>
import { PhCaretDown } from '@phosphor-icons/vue'

const model = defineModel()

defineProps({
	id: { type: String, default: null },
	options: { type: Array, default: () => [] }, // [{ value, label }]
	placeholder: { type: String, default: '– Auswählen –' },
	disabled: { type: Boolean, default: false },
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
	outline: 'border border-blue bg-light-blue rounded-xs',
}
</script>

<template>
	<div class="relative">
		<select
			:id="id"
			v-model="model"
			:disabled="disabled"
			class="appearance-none w-full pl-5 pr-30 min-h-32 ring-0! focus:ring-0! outline-none!"
			:class="hasError ? 'bg-light-red/30' : (variants[variant] ?? variants.filled)"
		>
			<option v-if="placeholder" :value="null">{{ placeholder }}</option>
			<option
				v-for="option in options"
				:key="option.value"
				:value="option.value"
			>
				{{ option.label }}
			</option>
		</select>
		<PhCaretDown
			:size="18"
			class="pointer-events-none absolute right-8 top-1/2 -translate-y-1/2 text-blue"
		/>
	</div>
</template>
