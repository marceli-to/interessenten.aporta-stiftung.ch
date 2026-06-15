<script setup>
import Panel from '@/components/ui/panels/Display.vue'
import Button from '@/components/ui/form/Button.vue'

// "Danger zone" sidebar panel. Deletion is soft: the application disappears from
// the list but the full aggregate stays intact and restorable at the DB level
// (see App\Actions\Application\Delete). Show.vue owns the actual delete flow
// (confirm + API + toast + redirect) and hands it in via onDelete.
defineProps({
	onDelete: { type: Function, required: true },
})
</script>

<template>
	<Panel variant="danger" title="Interessent löschen">
		<p class="text-sm text-black border-t border-blue/20 pt-15">
			Die Bewerbung wird aus der Liste entfernt, bleibt aber gespeichert und kann
			später wiederhergestellt werden.
		</p>
		<div class="mt-20">
			<Button variant="danger-solid" size="sm" icon="trash" @click="onDelete">
				Löschen
			</Button>
		</div>
	</Panel>
</template>
