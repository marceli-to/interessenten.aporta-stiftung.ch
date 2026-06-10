<script setup>
import { computed } from 'vue'

// Slug chips, used in both modes:
// - editable (default): every option is a toggle button; selected ones fill blue.
// - readonly: only the selected options render, as static tags (read view).
const model = defineModel({ type: Array, default: () => [] })

const props = defineProps({
	// `{ value, label }[]`
	options: { type: Array, default: () => [] },
	readonly: { type: Boolean, default: false },
})

const visible = computed(() =>
	props.readonly
		? props.options.filter((o) => model.value?.includes(o.value))
		: props.options
)

function toggle(value) {
	if (props.readonly) return
	const next = new Set(model.value)
	next.has(value) ? next.delete(value) : next.add(value)
	model.value = [...next]
}
</script>

<template>
	<div class="flex flex-wrap gap-5 py-3">
		<component
			:is="readonly ? 'span' : 'button'"
			v-for="option in visible"
			:key="option.value"
			:type="readonly ? undefined : 'button'"
			class="h-24 px-10 rounded-xs border border-blue text-sm inline-flex items-center leading-none"
			:class="readonly
				? 'text-blue'
				: [
					'transition-colors cursor-pointer',
					model.includes(option.value) ? 'bg-blue text-white' : 'text-blue hover:bg-light-blue',
				]"
			@click="toggle(option.value)"
		>
			{{ option.label }}
		</component>
		<template v-if="readonly && !visible.length">–</template>
	</div>
</template>
