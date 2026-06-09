import api from '@/api/axios'

export default {
	index: (params = {}) => api.get('/applications', { params }),
	show: (id) => api.get(`/applications/${id}`),
	update: (id, data) => api.put(`/applications/${id}`, data),
	updateStatus: (id, data) => api.put(`/applications/${id}/status`, data),
}
