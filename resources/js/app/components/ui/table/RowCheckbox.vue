<script setup>
import { ref, watch } from 'vue'

// Brand-styled selection checkbox for list rows and the select-all header. Pure
// native input + CSS state (per the design guidelines) — no JS class toggling.
// Supports the `indeterminate` state for "some rows selected" in the header,
// which can only be set on the DOM node, so we sync it via a ref + watcher.
const model = defineModel({ type: Boolean, default: false })

const props = defineProps({
	indeterminate: { type: Boolean, default: false },
	disabled: { type: Boolean, default: false },
	ariaLabel: { type: String, required: true },
})

const input = ref(null)

watch(
	() => props.indeterminate,
	(value) => {
		if (input.value) input.value.indeterminate = value
	},
	{ immediate: true }
)
</script>

<template>
	<span class="group inline-grid size-15 grid-cols-1 mt-2">
		<input
			ref="input"
			v-model="model"
			type="checkbox"
			:disabled="disabled"
			:aria-label="ariaLabel"
			class="checked:border-blue checked:bg-blue indeterminate:border-blue indeterminate:bg-blue focus-visible:outline-blue col-start-1 row-start-1 appearance-none rounded-sm border border-light-gray bg-white focus-visible:outline-2 focus-visible:outline-offset-2 forced-colors:appearance-auto cursor-pointer disabled:cursor-not-allowed disabled:border-light-gray disabled:bg-light-gray/30"
		/>
		<svg
			viewBox="0 0 14 14"
			fill="none"
			class="pointer-events-none col-start-1 row-start-1 size-7/8 self-center justify-self-center stroke-white"
		>
			<path
				d="M3 8L6 11L11 3.5"
				stroke-width="2"
				stroke-linecap="round"
				stroke-linejoin="round"
				class="group-not-has-checked:opacity-0"
			/>
			<path
				d="M3 7H11"
				stroke-width="2"
				stroke-linecap="round"
				stroke-linejoin="round"
				class="group-not-has-indeterminate:opacity-0"
			/>
		</svg>
	</span>
</template>
