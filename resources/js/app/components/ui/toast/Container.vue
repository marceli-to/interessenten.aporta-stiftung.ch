<script setup>
import { useToast } from '@/composables/useToast'
import ToastMessage from '@/components/ui/toast/Message.vue'

const { toasts, dismiss } = useToast()
</script>

<template>
	<div class="fixed bottom-24 right-24 z-50 w-full max-w-sm flex flex-col gap-8">
		<TransitionGroup
			enter-active-class="transition-all duration-200 ease-out"
			enter-from-class="opacity-0 translate-y-[8px]"
			enter-to-class="opacity-100 translate-y-0"
			leave-active-class="transition-all duration-150 ease-in"
			leave-from-class="opacity-100 translate-y-0"
			leave-to-class="opacity-0 translate-y-[8px]"
		>
			<ToastMessage
				v-for="toast in toasts.toasts"
				:key="toast.id"
				:toast="toast"
				@close="dismiss(toast.id)"
			/>
		</TransitionGroup>
	</div>
</template>
