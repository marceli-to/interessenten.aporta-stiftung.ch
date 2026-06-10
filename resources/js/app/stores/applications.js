import { defineStore } from 'pinia'
import api from '@/api/applications'

export const useApplicationsStore = defineStore('applications', {
	state: () => ({
		applications: [],
		statusCounts: {},
		loading: false,
		page: 1,
		lastPage: 1,
		total: 0,
		from: 0,
		to: 0,
	}),
	actions: {
		async fetch({ page = 1, perPage = 25, search = '', sort = 'opened_at', direction = 'desc', filters = {} } = {}) {
			this.loading = true
			try {
				const params = { page, per_page: perPage, sort, direction, ...filters }
				if (search) params.search = search
				const { data } = await api.index(params)
				this.applications = data.data
				this.statusCounts = data.status_counts ?? {}
				this.page = data.meta.current_page
				this.lastPage = data.meta.last_page
				this.total = data.meta.total
				this.from = data.meta.from ?? 0
				this.to = data.meta.to ?? 0
			} finally {
				this.loading = false
			}
		},
	},
})
