<script setup>
import { computed } from 'vue'
import { PhFolderOpen, PhClockClockwise, PhProhibit } from '@phosphor-icons/vue'

const props = defineProps({
	statusKey: { type: String, required: true },
	label: { type: String, required: true },
})

// Translates a status key into badge colours + icon.
const styles = {
	opened: { badge: 'bg-light-green text-green', icon: PhFolderOpen },
	extended: { badge: 'bg-light-violet text-violet', icon: PhClockClockwise },
	archived: { badge: 'bg-light-gray text-gray', icon: null },
	knif: { badge: 'bg-light-red text-red', icon: PhProhibit },
}

const style = computed(() => styles[props.statusKey] ?? styles.opened)
</script>

<template>
	<span
		class="inline-flex items-center gap-6 px-10 py-5 rounded-full text-xs font-medium"
		:class="style.badge">
		<component :is="style.icon" v-if="style.icon" :size="16" weight="regular" />
		{{ label }}
	</span>
</template>
