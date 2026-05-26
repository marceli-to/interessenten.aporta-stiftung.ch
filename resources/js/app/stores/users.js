import { defineStore } from 'pinia'
import api from '@/api/users'

export const useUsersStore = defineStore('users', {
	state: () => ({
		users: [],
		loading: false,
		errors: {},
	}),
	actions: {
		async fetch() {
			this.loading = true
			try {
				const { data } = await api.index()
				this.users = data.data
			} finally {
				this.loading = false
			}
		},
		async destroy(id) {
			await api.destroy(id)
			this.users = this.users.filter(u => u.id !== id)
		},
		setErrors(errors) {
			this.errors = errors ?? {}
		},
		clearErrors() {
			this.errors = {}
		},
	},
})
