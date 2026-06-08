import api from '@/api/axios'

export default {
	index: () => api.get('/applications'),
}
