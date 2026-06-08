import api from '@/api/axios'

export default {
	index: (params = {}) => api.get('/applications', { params }),
}
