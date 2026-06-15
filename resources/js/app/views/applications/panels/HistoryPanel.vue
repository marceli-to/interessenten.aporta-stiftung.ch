<script setup>
import { fmtDate } from '@/utils/format'
import Panel from '@/components/ui/panels/Display.vue'

// The "Verlauf" sidebar panel: the application's status audit trail, newest
// first (ordered in Application\Show). Read-only — status changes happen in the
// Info panel. Each event shows the target status label, the date, and who did
// it; the intake event has no actor and is shown as "Über Webformular".
defineProps({
	events: { type: Array, default: () => [] },
})
</script>

<template>
	<Panel variant="highlight" title="Verlauf">
		<div v-if="events.length" class="scrollbar-slim max-h-300 divide-y divide-black/20 overflow-y-auto border-y border-black/20">
			<div v-for="event in events" :key="event.id" class="py-10">
				<div class="flex items-baseline justify-between gap-10">
					<span class="font-bold text-blue">{{ event.status.label }}</span>
					<span class="shrink-0 text-sm text-black/50">{{ fmtDate(event.occurred_at) }}</span>
				</div>
				<p class="mt-5 text-blue">{{ event.actor ?? 'Über Webformular' }}</p>
			</div>
		</div>
		<p v-else class="border-t border-black/20 pt-15 text-sm text-gray">
			Kein Verlauf vorhanden.
		</p>
	</Panel>
</template>
