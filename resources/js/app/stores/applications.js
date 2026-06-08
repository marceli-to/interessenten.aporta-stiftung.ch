import { defineStore } from 'pinia'
import api from '@/api/applications'

export const useApplicationsStore = defineStore('applications', {
	state: () => ({
		applications: [],
		loading: false,
	}),
	actions: {
		async fetch() {
			this.loading = true
			try {
				const { data } = await api.index()
				this.applications = data.data
			} finally {
				this.loading = false
			}
		},
	},
})
