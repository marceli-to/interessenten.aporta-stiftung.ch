import api from '@/api/axios'

export default {
	index: (page = 1, perPage = 25) => api.get('/applications', { params: { page, per_page: perPage } }),
}
