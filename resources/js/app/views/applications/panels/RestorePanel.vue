<script setup>
import Panel from '@/components/ui/panels/Display.vue'
import Button from '@/components/ui/form/Button.vue'

// Shown in place of the DeletePanel when the application is soft-deleted (see
// Show.vue). Restoring is non-destructive and reversible, so it's a plain white
// panel with a direct button — no confirm dialog. Show.vue owns the actual
// restore flow (API + toast + redirect) and hands it in via onRestore.
defineProps({
	onRestore: { type: Function, required: true },
})
</script>

<template>
	<Panel title="Bewerbung wiederherstellen">
		<p class="text-sm text-black">
			Diese Bewerbung ist gelöscht. Beim Wiederherstellen wird sie mit allen
			Daten zurück in die Liste aufgenommen.
		</p>
		<div class="mt-15">
			<Button variant="primary" size="sm" icon="arrows-clockwise" @click="onRestore">
				Wiederherstellen
			</Button>
		</div>
	</Panel>
</template>
