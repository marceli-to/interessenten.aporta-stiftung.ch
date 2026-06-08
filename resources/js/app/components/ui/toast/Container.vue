<script setup>
import { useToast } from '@/composables/useToast'
import ToastMessage from '@/components/ui/toast/Message.vue'

const { toasts, dismiss } = useToast()
</script>

<template>
	<div class="fixed bottom-25 right-25 z-50 w-full max-w-sm flex flex-col gap-10">
		<TransitionGroup
			enter-active-class="transition-all duration-200 ease-out"
			enter-from-class="opacity-0 translate-y-[10px]"
			enter-to-class="opacity-100 translate-y-0"
			leave-active-class="transition-all duration-150 ease-in"
			leave-from-class="opacity-100 translate-y-0"
			leave-to-class="opacity-0 translate-y-[10px]"
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
