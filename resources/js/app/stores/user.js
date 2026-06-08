import { defineStore } from 'pinia'
import api from '@/api/user'

export const useUserStore = defineStore('user', {
	state: () => ({
		user: null,
	}),

	actions: {
		async fetchUser() {
			const { data } = await api.me()
			this.user = data.data
		},
	},
})
