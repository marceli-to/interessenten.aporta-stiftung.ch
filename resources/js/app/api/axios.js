import axios from 'axios'

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? ''

const http = axios.create({
	baseURL: '/api/dashboard',
	withCredentials: true,
	headers: {
		'X-Requested-With': 'XMLHttpRequest',
		'X-CSRF-TOKEN': csrfToken,
		Accept: 'application/json',
	},
})

export default http
