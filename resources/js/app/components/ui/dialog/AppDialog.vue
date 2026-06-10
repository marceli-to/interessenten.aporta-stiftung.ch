<script setup>
import { ref, watch } from 'vue'
import { PhX } from '@phosphor-icons/vue'
import Heading2 from '@/components/ui/headings/H2.vue'

const props = defineProps({
	open: { type: Boolean, default: false },
	title: { type: String, default: null },
	size: { type: String, default: 'sm' }, // sm, md, lg
})

const emit = defineEmits(['close'])

const dialogRef = ref(null)

const sizes = {
	sm: 'max-w-360',
	md: 'max-w-480',
	lg: 'max-w-640',
}

watch(() => props.open, (val) => {
	if (val) {
		dialogRef.value?.showModal()
	} else {
		dialogRef.value?.close()
	}
})

function onClose() {
	document.activeElement?.blur()
	emit('close')
}
</script>

<template>
	<dialog
		ref="dialogRef"
		class="p-0 m-auto w-full bg-white rounded-2xl shadow-xl backdrop:bg-black/50"
		:class="sizes[size]"
		@close="onClose"
		@click.self="onClose"
	>
		<div class="px-20 py-25">
			<!-- Header -->
			<div v-if="title || $slots.header" class="flex items-start mb-15">
				<slot name="header">
					<Heading2 v-if="title" class="text-black!">{{ title }}</Heading2>
				</slot>
				<button
					type="button"
					class="ml-auto text-gray hover:text-blue transition-colors cursor-pointer"
					@click="onClose"
				>
					<PhX :size="18" weight="regular" />
				</button>
			</div>

			<!-- Body -->
			<div>
				<slot />
			</div>

			<!-- Footer -->
			<div v-if="$slots.footer" class="mt-25">
				<slot name="footer" />
			</div>
		</div>
	</dialog>
</template>
