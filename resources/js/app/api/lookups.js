import axios from 'axios'

// Lookups are public, cacheable reference data served outside the /api/dashboard
// prefix (GET /api/v1/lookups), so they use their own bare client rather than the
// dashboard axios instance. The backend sets ETag / Cache-Control headers.
const http = axios.create({
	withCredentials: true,
	headers: {
		'X-Requested-With': 'XMLHttpRequest',
		Accept: 'application/json',
	},
})

export default {
	show: () => http.get('/api/v1/lookups'),
}
