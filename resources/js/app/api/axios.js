import axios from 'axios'
import { useToast } from '@/composables/useToast'

const http = axios.create({
	baseURL: '/api/dashboard',
	withCredentials: true,
	headers: {
		'X-Requested-With': 'XMLHttpRequest',
		Accept: 'application/json',
	},
})

// Read the CSRF token fresh on every request from the <meta> tag Laravel renders,
// so it stays valid across the SPA's lifetime (e.g. after a 419 re-login).
http.interceptors.request.use((config) => {
	const token = document.querySelector('meta[name="csrf-token"]')?.content
	if (token) config.headers['X-CSRF-TOKEN'] = token
	return config
})

// The single chokepoint for all HTTP errors. It surfaces cross-cutting failures
// (auth, permission, server, network) via toast and ALWAYS re-rejects so the
// store/view catch block still runs — global and local handling compose.
//
// 422 is the one exception: validation errors are field-specific, so they pass
// through untouched for useUsersStore/the view to render inline.
http.interceptors.response.use(
	(response) => response,
	(error) => {
		const toast = useToast()
		const status = error.response?.status

		if (status === 401 || status === 419) {
			// Session expired or CSRF token stale → SPA state is unrecoverable.
			// A hard redirect re-establishes auth and refreshes the token.
			window.location.href = '/login'
		} else if (status === 403) {
			toast.warning('Sie haben keine Berechtigung für diese Aktion.')
		} else if (status === 404) {
			toast.warning('Die angeforderte Ressource wurde nicht gefunden.')
		} else if (status === 408) {
			toast.error('Zeitüberschreitung der Anfrage. Bitte versuchen Sie es erneut.')
		} else if (status === 422) {
			// pass through — handled inline by the store/view at the call site
		} else if (status === 429) {
			toast.warning('Zu viele Anfragen. Bitte warten Sie einen Moment.')
		} else if (status >= 500) {
			toast.error('Serverfehler. Bitte versuchen Sie es später erneut.')
		} else if (status >= 400) {
			// any other 4xx (400, 405, 409, 413, …) we don't special-case
			toast.error('Die Anfrage konnte nicht verarbeitet werden.')
		} else if (!error.response) {
			// no response at all → network down, CORS, or timeout
			toast.error('Netzwerkfehler. Bitte überprüfen Sie Ihre Verbindung.')
		}

		return Promise.reject(error)
	}
)

export default http
