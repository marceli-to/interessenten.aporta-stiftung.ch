import { createRouter, createWebHistory } from 'vue-router'

import ApplicationListView from '@/views/applications/ApplicationListView.vue'
import ApplicationDetailView from '@/views/applications/ApplicationDetailView.vue'
import UsersIndex from '@/views/users/Index.vue'
import UsersForm from '@/views/users/Form.vue'

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
		component: UsersIndex,
		meta: { requiresAdmin: true },
	},
	{
		path: '/users/new',
		name: 'users.create',
		component: UsersForm,
		meta: { requiresAdmin: true },
	},
	{
		path: '/users/:id',
		name: 'users.edit',
		component: UsersForm,
		props: true,
		meta: { requiresAdmin: true },
	},
]

export default createRouter({
	history: createWebHistory('/dashboard/'),
	routes,
})
