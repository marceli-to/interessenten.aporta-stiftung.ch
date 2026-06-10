<script setup>
import { ref, onMounted } from 'vue'
import { useLookupsStore } from '@/stores/lookups'
import { PhFolderOpen, PhClockClockwise, PhArchive, PhProhibit } from '@phosphor-icons/vue'
import Button from '@/components/ui/form/Button.vue'
import SearchInput from '@/components/ui/form/Search.vue'
import Input from '@/components/ui/form/Input.vue'
import ChipGroup from '@/components/ui/form/ChipGroup.vue'
import Selectable from '@/components/ui/form/Selectable.vue'
import Label from '@/components/ui/form/Label.vue'

// The list's free-text search lives on the parent's list query; everything else
// here is local filter state, not yet wired to the query.
const search = defineModel('search')

const lookups = useLookupsStore()

const showFilter = ref(true)
const statusFilter = ref('opened')
const moveInFrom = ref(null)
const moveInTo = ref(null)
const rentMin = ref(null)
const rentMax = ref(null)
const districts = ref([])
const rooms = ref([])

// Status toggles. Counts are placeholders until the API supplies them.
const statusOptions = [
	{ value: 'opened', label: 'Eröffnet', count: 124, icon: PhFolderOpen },
	{ value: 'extended', label: 'Verlängert', count: 87, icon: PhClockClockwise },
	{ value: 'archived', label: 'Archiviert', count: 412, icon: PhArchive },
	{ value: 'knif', label: 'KNIF', count: 25, icon: PhProhibit },
]

function toggleStatus(value) {
	statusFilter.value = statusFilter.value === value ? null : value
}

function resetFilter() {
	statusFilter.value = null
	moveInFrom.value = moveInTo.value = null
	rentMin.value = rentMax.value = null
	districts.value = []
	rooms.value = []
}

// Reference sets for the Stadtkreis / Zimmer chips (no-op if already loaded).
onMounted(() => lookups.fetch())
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
						:count="option.count"
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

		<div class="flex justify-end mt-10">
			<Button variant="ghost" size="sm" class="underline" @click="resetFilter">
				Zurücksetzen
			</Button>
		</div>
	</div>
	<!--// Filter container -->
</template>
