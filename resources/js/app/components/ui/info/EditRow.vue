<script setup>
import InfoRow from '@/components/ui/info/Row.vue'

// The editable counterpart of info/Row: same "label | value" grid, but the value
// column holds a form control (default slot) plus an inline validation message.
// Lets a panel's edit mode mirror its read mode row-for-row.
defineProps({
	label: { type: String, default: null },
	error: { type: [String, Array], default: null },
})
</script>

<template>
	<InfoRow :label="label">
		<slot />
		<p v-if="error" class="mt-5 text-sm text-red">
			{{ Array.isArray(error) ? error[0] : error }}
		</p>
	</InfoRow>
</template>
