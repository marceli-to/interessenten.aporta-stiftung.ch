import { createRouter, createWebHistory } from 'vue-router'

import ApplicationsIndex from '@/views/applications/Index.vue'
import ApplicationsShow from '@/views/applications/Show.vue'
import UsersIndex from '@/views/users/Index.vue'
import UsersForm from '@/views/users/Form.vue'

const routes = [
	{
		path: '/',
		name: 'applications.index',
		component: ApplicationsIndex,
	},
	{
		path: '/applications/:id',
		name: 'applications.show',
		component: ApplicationsShow,
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
