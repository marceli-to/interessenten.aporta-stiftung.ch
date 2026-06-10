<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useLookupsStore } from '@/stores/lookups'
import { useApplicationsStore } from '@/stores/applications'
import { PhFolderOpen, PhClockClockwise, PhArchive, PhProhibit } from '@phosphor-icons/vue'
import Button from '@/components/ui/form/Button.vue'
import SearchInput from '@/components/ui/form/Search.vue'
import Input from '@/components/ui/form/Input.vue'
import ChipGroup from '@/components/ui/form/ChipGroup.vue'
import Selectable from '@/components/ui/form/Selectable.vue'
import Label from '@/components/ui/form/Label.vue'

// Search is owned by the parent's list query; the rest of the filters are mirrored
// straight into the URL so they survive navigation / the detail back-link and are
// picked up by useListQuery, which forwards them to the fetch.
const search = defineModel('search')

const route = useRoute()
const router = useRouter()
const lookups = useLookupsStore()
const store = useApplicationsStore()

const showFilter = ref(true)

const FILTER_KEYS = ['status', 'move_in_from', 'move_in_to', 'rent_min', 'rent_max', 'districts', 'rooms']

// A writable computed over one URL query param. Arrays are stored comma-joined;
// scalars are dropped from the URL when cleared. Every write resets to page 1.
function filterRef(key, { array = false, number = false } = {}) {
	return computed({
		get() {
			const raw = route.query[key]
			if (array) return raw ? String(raw).split(',') : []
			if (raw == null || raw === '') return null
			return number ? Number(raw) : raw
		},
		set(value) {
			const encoded = array
				? (value?.length ? value.join(',') : undefined)
				: (value === '' || value == null ? undefined : String(value))
			const query = { ...route.query, page: 1 }
			if (encoded === undefined) delete query[key]
			else query[key] = encoded
			router.push({ query })
		},
	})
}

const statusFilter = filterRef('status')
const moveInFrom = filterRef('move_in_from')
const moveInTo = filterRef('move_in_to')
const rentMin = filterRef('rent_min', { number: true })
const rentMax = filterRef('rent_max', { number: true })
const districts = filterRef('districts', { array: true })
const rooms = filterRef('rooms', { array: true })

const statusOptions = [
	{ value: 'opened', label: 'Eröffnet', icon: PhFolderOpen },
	{ value: 'extended', label: 'Verlängert', icon: PhClockClockwise },
	{ value: 'archived', label: 'Archiviert', icon: PhArchive },
	{ value: 'knif', label: 'KNIF', icon: PhProhibit },
]

function toggleStatus(value) {
	statusFilter.value = statusFilter.value === value ? null : value
}

function resetFilter() {
	const query = { ...route.query, page: 1 }
	FILTER_KEYS.forEach((key) => delete query[key])
	router.push({ query })
}

onMounted(() => {
	// Reference sets for the Stadtkreis / Zimmer chips (no-op if already loaded).
	lookups.fetch()

	// On a fresh landing (no query at all) default to open applications — the
	// common working view. A `replace` keeps it out of history, and because this
	// only runs on mount it doesn't fight an explicit "Zurücksetzen".
	if (Object.keys(route.query).length === 0) {
		router.replace({ query: { status: 'opened' } })
	}
})
</script>

<template>

	<!-- Filter button and search field -->
	<div class="flex items-center gap-20 mb-30 w-full">
		<Button variant="primary" icon="faders" size="md" @click="showFilter = !showFilter">
			Filter
		</Button>
		<SearchInput v-model="search" placeholder="Suche nach Name, Nummer, Ort" />
	</div>
	<!-- // Filter button and search field -->

	<!-- Filter container -->
	<div v-if="showFilter" class="mt-50 mb-40 px-84">
		<div class="flex flex-wrap gap-x-50 gap-y-25">

			<!-- Status -->
			<div class="min-w-200">
				<Label>
					Status
				</Label>
				<div class="flex flex-col gap-10">
					<Selectable
						v-for="option in statusOptions"
						:key="option.value"
						:icon="option.icon"
						:label="option.label"
						:count="store.statusCounts[option.value] ?? null"
						:active="statusFilter === option.value"
						@click="toggleStatus(option.value)"
					/>
				</div>
			</div>

			<!-- Date and rent ranges -->
			<div class="flex flex-col gap-20">
				<div>
					<Label>
						Mietbeginn
					</Label>
					<div class="flex items-center gap-10">
						<Input v-model="moveInFrom" type="date" variant="outline" class="w-150" placeholder="tt.mm.jjjj" />
						<span class="text-blue/50">–</span>
						<Input v-model="moveInTo" type="date" variant="outline" class="w-150" placeholder="tt.mm.jjjj" />
					</div>
				</div>

				<div>
					<Label>
						Max. Bruttomiete
					</Label>
					<div class="flex items-center gap-10">
						<Input v-model.number="rentMin" type="number" variant="outline" class="w-150" placeholder="1200" />
						<span class="text-blue/50">–</span>
						<Input v-model.number="rentMax" type="number" variant="outline" class="w-150" placeholder="3800" />
					</div>
				</div>
			</div>

			<!-- District and room chips -->
			<div class="flex flex-col gap-20">
				<div>
					<Label>
						Stadtkreis
					</Label>
					<ChipGroup v-model="districts" :options="lookups.options('districts')" size="md" />
				</div>
				<div>
					<Label>
						Zimmer
					</Label>
					<ChipGroup v-model="rooms" :options="lookups.options('rooms')" size="md" />
				</div>
			</div>

		</div>

		<div class="flex justify-end">
			<Button variant="ghost" size="sm" class="underline" @click="resetFilter">
				Zurücksetzen
			</Button>
		</div>
	</div>
	<!--// Filter container -->
</template>
