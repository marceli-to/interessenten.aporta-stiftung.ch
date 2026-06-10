<script setup>
import InfoRow from '@/components/ui/info/Row.vue'

// The editable counterpart of info/Row: same "label | value" grid, but the value
// column holds a form control (default slot). On validation error the label turns
// red (and the control is tinted red via its own `hasError` prop). How the message
// itself is surfaced is still TBD — handled elsewhere for now.
defineProps({
	label: { type: String, default: null },
	error: { type: [String, Array], default: null },
})
</script>

<template>
	<InfoRow :label="label">
		<template #label>
			<label class="font-bold text-sm" :class="{ 'text-red': error }">
				{{ label }}
			</label>
		</template>

		<slot />
	</InfoRow>
</template>
