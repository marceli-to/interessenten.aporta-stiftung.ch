<script setup>
import { PhArrowSquareOut, PhDownloadSimple, PhTrash } from '@phosphor-icons/vue'

// Floating action bar for list multi-select. Pinned bottom-center, it appears
// (with a short slide/fade) only while at least one row is selected and follows
// the page as it scrolls. The parent owns the selection set and the actions; we
// only render the count, the actions, and a "clear" affordance.
//
// Selection is filter-scoped: `count` is how many are selected, `total` is the
// size of the current filtered result. When the whole page is ticked but more
// rows match (`canSelectAll`), we offer the "Alle N auswählen" upgrade, which
// switches the parent into all-matching mode (`allMatching`).
//
// Actions, left → right: Öffnen (browse the selection — Resultatansicht),
// Exportieren, Löschen. Löschen is destructive, so it gets the solid red
// treatment to set it apart from the two neutral outline actions.
defineProps({
	count: { type: Number, required: true },
	total: { type: Number, default: 0 },
	canSelectAll: { type: Boolean, default: false },
	allMatching: { type: Boolean, default: false },
})

defineEmits(['selectAll', 'clear', 'open', 'export', 'delete'])
</script>

<template>
	<Transition
		enter-active-class="transition duration-200 ease-out"
		enter-from-class="opacity-0 translate-y-15"
		enter-to-class="opacity-100 translate-y-0"
		leave-active-class="transition duration-150 ease-in"
		leave-from-class="opacity-100 translate-y-0"
		leave-to-class="opacity-0 translate-y-15"
	>
		<div
			v-if="count > 0"
			class="fixed inset-x-0 bottom-30 z-40 flex justify-center px-20 pointer-events-none"
		>
			<div
				class="pointer-events-auto flex items-center gap-20 rounded-full bg-blue py-10 pl-25 pr-15 text-white shadow-xl"
			>
				<span class="text-sm whitespace-nowrap">
					<template v-if="allMatching && count === total">
						<span class="font-bold tabular-nums">Alle {{ total }}</span>
						ausgewählt
					</template>
					<template v-else>
						<span class="font-bold tabular-nums">{{ count }}</span>
						von {{ total }} ausgewählt
					</template>
				</span>

				<button
					v-if="canSelectAll"
					type="button"
					class="text-sm text-white underline hover:text-white/80"
					@click="$emit('selectAll')"
				>
					Alle {{ total }} auswählen
				</button>

				<button
					type="button"
					class="text-sm text-white/70 underline hover:text-white"
					@click="$emit('clear')"
				>
					Abwählen
				</button>

				<div class="h-20 w-px bg-white/20" />

				<div class="flex items-center gap-10">
					<button
						type="button"
						class="inline-flex h-30 items-center gap-5 rounded-full border border-white/40 pl-10 pr-12 text-sm hover:bg-white/10"
						@click="$emit('open')"
					>
						<PhArrowSquareOut :size="16" weight="regular" />
						Öffnen
					</button>
					<button
						type="button"
						class="inline-flex h-30 items-center gap-5 rounded-full border border-white/40 pl-10 pr-12 text-sm hover:bg-white/10"
						@click="$emit('export')"
					>
						<PhDownloadSimple :size="16" weight="regular" />
						Exportieren
					</button>
					<button
						type="button"
						class="inline-flex h-30 items-center gap-5 rounded-full bg-red pl-10 pr-12 text-sm font-medium hover:bg-red/90"
						@click="$emit('delete')"
					>
						<PhTrash :size="16" weight="regular" />
						Löschen
					</button>
				</div>
			</div>
		</div>
	</Transition>
</template>
