import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import { useToast } from '@/composables/useToast'

// Drop lingering failure toasts after navigation so a stale error/warning from
// one page never follows the user to the next.
router.afterEach(() => useToast().clearErrors())

createApp(App)
	.use(createPinia())
	.use(router)
	.mount('#app')
