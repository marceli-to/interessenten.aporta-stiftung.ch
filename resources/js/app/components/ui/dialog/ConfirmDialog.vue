<script setup>
import AppDialog from './AppDialog.vue'
import Button from '@/components/ui/form/Button.vue'

defineProps({
	open: { type: Boolean, default: false },
	title: { type: String, default: 'Bestätigen' },
	message: { type: String, default: 'Sind Sie sicher?' },
	confirmLabel: { type: String, default: 'Bestätigen' },
	cancelLabel: { type: String, default: 'Abbrechen' },
	destructive: { type: Boolean, default: false },
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
	<AppDialog :open="open" :title="title" @close="emit('cancel')">
		<p class="text-sm text-black">{{ message }}</p>

		<template #footer>
			<div class="flex justify-end gap-15">
				<Button variant="outline" size="sm" @click="emit('cancel')">
					{{ cancelLabel }}
				</Button>
				<Button
					:variant="destructive ? 'danger-solid' : 'primary'"
					size="sm"
					@click="emit('confirm')"
				>
					{{ confirmLabel }}
				</Button>
			</div>
		</template>
	</AppDialog>
</template>
