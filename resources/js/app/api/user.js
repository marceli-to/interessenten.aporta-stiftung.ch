import api from '@/api/axios'

export default {
	me: () => api.get('/me'),
}
