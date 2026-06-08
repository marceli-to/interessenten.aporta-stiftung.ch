import { reactive, readonly } from 'vue'

// Singleton state — declared at module scope, OUTSIDE the composable, so every
// useToast() call (view, store, axios interceptor) shares one queue rendered by
// the single mounted <ToastContainer>.
const state = reactive({ toasts: [] })

let nextId = 0

// success/info are transient and auto-dismiss; error/warning persist until the
// user dismisses them or navigates away — failures should stay readable.
const AUTO_DISMISS = { success: 3500, info: 4000 }

function add(type, message) {
	const id = nextId++
	state.toasts.push({ id, type, message })
	const ttl = AUTO_DISMISS[type]
	if (ttl) setTimeout(() => dismiss(id), ttl)
	return id
}

function dismiss(id) {
	const index = state.toasts.findIndex(t => t.id === id)
	if (index > -1) state.toasts.splice(index, 1)
}

// Remove the persistent failure toasts (error + warning). Called on navigation
// so a stale failure from one page never follows the user to the next.
function clearErrors() {
	state.toasts = state.toasts.filter(t => t.type !== 'error' && t.type !== 'warning')
}

export function useToast() {
	return {
		toasts: readonly(state),
		success: (message) => add('success', message),
		error: (message) => add('error', message),
		warning: (message) => add('warning', message),
		info: (message) => add('info', message),
		dismiss,
		clearErrors,
	}
}
