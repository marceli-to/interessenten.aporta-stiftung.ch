<script setup>
import { useRoute, useRouter } from 'vue-router'
import { useApplicationsStore } from '@/stores/applications'
import { useListQuery } from '@/composables/useListQuery'
import { fmtDate, fmtMoney, fmtList } from '@/utils/format'
import Panel from '@/components/ui/panels/Display.vue'
import Pagination from '@/components/ui/pagination/Pagination.vue'
import StatusBadge from '@/components/ui/badges/Status.vue'
import TableHeadCell from '@/components/ui/table/HeadCell.vue'
import TableCell from '@/components/ui/table/Cell.vue'
import Filter from './Filter.vue'

const route = useRoute()
const router = useRouter()
const store = useApplicationsStore()

const { sort, direction, search, goToPage, toggleSort } = useListQuery({
	fetch: store.fetch,
	perPage: 15,
})

// Visual treatment per row. `flagged` (Wichtig) overrides the open/extended
// status; an archived application is terminal and overrides everything.
const styles = {
	opened: { text: 'text-green' },
	extended: { text: 'text-violet' },
	flagged: { text: 'text-red' },
	archived: { text: 'text-gray' },
	knif: { text: 'text-red' },
}

function display(application) {
	let key = application.status.value
	let label = application.status.label

	if (key !== 'archived' && key !== 'knif' && application.flagged) {
		key = 'flagged'
		label = 'Wichtig'
	}

	return { key, label, ...styles[key] }
}

// Carry the current list URL (search / page / sort) into history state so the
// detail view's back link can return to the exact same filtered list.
function open(application) {
	router.push({
		name: 'applications.show',
		params: { id: application.id },
		state: { from: route.fullPath },
	})
}
</script>

<template>
  <Filter v-model:search="search" />


  <Panel>
    <div class="overflow-x-auto">
      <table class="w-full text-sm whitespace-nowrap">
        <thead class="text-left text-black border-b border-blue/20">
          <tr>
            <TableHeadCell variant="first" sort-key="reference_number" :sort="sort" :direction="direction" @sort="toggleSort">
              Nr.
            </TableHeadCell>
            <TableHeadCell>
              Hauptmieter
            </TableHeadCell>
            <TableHeadCell sort-key="status" :sort="sort" :direction="direction" @sort="toggleSort">
              Status
            </TableHeadCell>
            <TableHeadCell sort-key="opened_at" :sort="sort" :direction="direction" @sort="toggleSort">
              Angemeldet
            </TableHeadCell>
            <TableHeadCell sort-key="extended_at" :sort="sort" :direction="direction" @sort="toggleSort">
              Verlängert
            </TableHeadCell>
            <TableHeadCell sort-key="earliest_move_in" :sort="sort" :direction="direction" @sort="toggleSort">
              Mietbeginn
            </TableHeadCell>
            <TableHeadCell class="text-right" sort-key="max_gross_rent" :sort="sort" :direction="direction" @sort="toggleSort">
              Max. Miete
            </TableHeadCell>
            <TableHeadCell sort-key="total_persons" :sort="sort" :direction="direction" @sort="toggleSort">
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
          <template v-if="store.loading">
            <tr>
              <td colspan="11">
                Laden …
              </td>
            </tr>
          </template>
          <template v-else>
            <tr
              v-for="application in store.applications"
              :key="application.id"
              class="cursor-pointer hover:bg-light-gray/10 align-top"
              @click="open(application)">
              <TableCell variant="first" class="font-bold" :class="display(application).text">
                {{ application.reference_number }}
              </TableCell>

              <TableCell>
                <div class="font-bold" :class="display(application).text">
                  {{ application.main_applicant?.first_name }}
                  {{ application.main_applicant?.last_name }}
                </div>
                <div class="max-w-[160px] truncate">
                  {{ application.main_applicant?.street }}
                </div>
                <div>
                  {{ application.main_applicant?.postal_code }} {{ application.main_applicant?.city }}
                </div>
              </TableCell>

              <TableCell>
                <StatusBadge
                  :statusKey="display(application).key"
                  :label="display(application).label"
                />
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
                {{ application.main_applicant?.income_bracket ?? '–' }}
              </TableCell>
            </tr>
            <tr v-if="!store.applications.length">
              <td colspan="11" class="py-30 text-center text-sm text-light-gray">
                Keine Anmeldungen gefunden.
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </Panel>

  <div class="mt-25">
    <Pagination
      :page="store.page"
      :last-page="store.lastPage"
      :total="store.total"
      :from="store.from"
      :to="store.to"
      @change="goToPage"
    />
  </div>
</template>
