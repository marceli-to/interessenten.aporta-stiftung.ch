<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { PhFolderOpen, PhClockClockwise, PhStar } from '@phosphor-icons/vue'
import { useApplicationsStore } from '@/stores/applications'
import { fmtDate, fmtMoney, fmtList } from '@/utils/format'
import Panel from '@/components/ui/Panel.vue'
import TableHeadCell from '@/components/ui/table/HeadCell.vue'
import TableCell from '@/components/ui/table/Cell.vue'

const router = useRouter()
const store = useApplicationsStore()

// Visual treatment per row. `flagged` (Wichtig) overrides the open/extended
// status; an archived application is terminal and overrides everything.
const styles = {
	opened: { text: 'text-green', badge: 'bg-light-green text-green', icon: PhFolderOpen },
	extended: { text: 'text-violet', badge: 'bg-light-violet text-violet', icon: PhClockClockwise },
	flagged: { text: 'text-red', badge: 'bg-light-red text-red', icon: PhStar },
	archived: { text: 'text-light-gray', badge: 'bg-gray text-white', icon: null },
}

function display(application) {
	let key = application.status.value
	let label = application.status.label

	if (key !== 'archived' && application.flagged) {
		key = 'flagged'
		label = 'Wichtig'
	}

	return { key, label, ...styles[key] }
}

function open(application) {
	router.push({ name: 'applications.show', params: { id: application.id } })
}

onMounted(() => store.fetch())
</script>

<template>
  <Panel>
    <div class="overflow-x-auto">
      <template v-if="store.loading">
        <div class="text-sm text-blue">
          Laden …
        </div>
      </template>
      <template v-else>
        <table class="w-full text-sm whitespace-nowrap">
          <thead class="text-left uppercase text-black border-b border-blue/20">
            <tr>
              <TableHeadCell variant="first">
                Nr.
              </TableHeadCell>
              <TableHeadCell>
                Hauptmieter
              </TableHeadCell>
              <TableHeadCell>
                Status
              </TableHeadCell>
              <TableHeadCell>
                Angemeldet
              </TableHeadCell>
              <TableHeadCell>
                Verlängert
              </TableHeadCell>
              <TableHeadCell>
                Mietbeginn
              </TableHeadCell>
              <TableHeadCell class="text-right">
                Max. Miete
              </TableHeadCell>
              <TableHeadCell>
                Pers.
              </TableHeadCell>
              <TableHeadCell>
                Zimmer
              </TableHeadCell>
              <TableHeadCell>
                Kreise
              </TableHeadCell>
              <TableHeadCell variant="last">
                Einkommen
              </TableHeadCell>
            </tr>
          </thead>
          <tbody class="divide-y divide-blue/20">
            <tr
              v-for="application in store.applications"
              :key="application.id"
              class="cursor-pointer hover:bg-blue/5 align-top"
              :class="display(application).key === 'archived' ? 'text-light-gray' : 'text-gray'"
              @click="open(application)"
            >
              <TableCell variant="first" class="font-bold" :class="display(application).text">
                {{ application.reference_number }}
              </TableCell>

              <TableCell>
                <div class="font-bold" :class="display(application).text">
                  {{ application.main_applicant?.salutation }}
                  {{ application.main_applicant?.first_name }}
                  {{ application.main_applicant?.last_name }}
                </div>
                <div>{{ application.main_applicant?.street }}</div>
                <div>{{ application.main_applicant?.postal_code }} {{ application.main_applicant?.city }}</div>
              </TableCell>

              <TableCell>
                <span
                  class="inline-flex items-center gap-6 px-10 py-4 rounded-full text-xs font-medium"
                  :class="display(application).badge">
                  <component
                    :is="display(application).icon"
                    v-if="display(application).icon"
                    :size="14"
                    weight="bold"
                  />
                  {{ display(application).label }}
                </span>
              </TableCell>

              <TableCell>
                {{ fmtDate(application.opened_at) }}
              </TableCell>
              <TableCell>
                {{ fmtDate(application.extended_at) }}
              </TableCell>
              <TableCell>
                {{ fmtDate(application.earliest_move_in) }}
              </TableCell>
              <TableCell class="text-right tabular-nums">
                {{ fmtMoney(application.max_gross_rent) }}
              </TableCell>
              <TableCell>
                {{ application.total_persons }}
              </TableCell>
              <TableCell>
                {{ fmtList(application.rooms) }}
              </TableCell>
              <TableCell>
                {{ fmtList(application.districts) }}
              </TableCell>
              <TableCell variant="last">
                {{ application.main_applicant?.income_bracket ?? '—' }}
              </TableCell>
            </tr>
          </tbody>
        </table>
      </template>
    </div>
  </Panel>
</template>
