import api from '@/api/axios'

export default {
	index: (params = {}) => api.get('/applications', { params }),
	show: (id) => api.get(`/applications/${id}`),
	update: (id, data) => api.put(`/applications/${id}`, data),
	destroy: (id) => api.delete(`/applications/${id}`),
	bulkDestroy: (payload) => api.post('/applications/bulk-delete', payload),
	bulkRestore: (payload) => api.post('/applications/bulk-restore', payload),
	bulkResolve: (payload) => api.post('/applications/bulk-resolve', payload),
	// Streams a PDF back; errors (cap/empty selection) come back as a JSON blob.
	bulkExport: (payload) => api.post('/applications/bulk-export', payload, { responseType: 'blob' }),
	restore: (id) => api.post(`/applications/${id}/restore`),
	updateStatus: (id, data) => api.put(`/applications/${id}/status`, data),

	storeNote: (id, data) => api.post(`/applications/${id}/notes`, data),
	updateNote: (id, noteId, data) => api.put(`/applications/${id}/notes/${noteId}`, data),
	destroyNote: (id, noteId) => api.delete(`/applications/${id}/notes/${noteId}`),
}
