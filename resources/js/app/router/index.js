import { createRouter, createWebHistory } from 'vue-router'

import ApplicationListView from '@/views/applications/ApplicationListView.vue'
import ApplicationDetailView from '@/views/applications/ApplicationDetailView.vue'
import UserListView from '@/views/users/UserListView.vue'
import UserEditView from '@/views/users/UserEditView.vue'

const routes = [
	{
		path: '/',
		name: 'applications.index',
		component: ApplicationListView,
	},
	{
		path: '/applications/:id',
		name: 'applications.show',
		component: ApplicationDetailView,
		props: true,
	},
	{
		path: '/users',
		name: 'users.index',
		component: UserListView,
		meta: { requiresAdmin: true },
	},
	{
		path: '/users/:id',
		name: 'users.edit',
		component: UserEditView,
		props: true,
		meta: { requiresAdmin: true },
	},
]

export default createRouter({
	history: createWebHistory('/dashboard/'),
	routes,
})
