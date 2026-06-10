import api from '@/api/axios'

export default {
	index: (params = {}) => api.get('/applications', { params }),
	show: (id) => api.get(`/applications/${id}`),
	update: (id, data) => api.put(`/applications/${id}`, data),
	destroy: (id) => api.delete(`/applications/${id}`),
	updateStatus: (id, data) => api.put(`/applications/${id}/status`, data),

	storeNote: (id, data) => api.post(`/applications/${id}/notes`, data),
	updateNote: (id, noteId, data) => api.put(`/applications/${id}/notes/${noteId}`, data),
	destroyNote: (id, noteId) => api.delete(`/applications/${id}/notes/${noteId}`),
}
