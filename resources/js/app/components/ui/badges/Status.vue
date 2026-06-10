<script setup>
import { computed } from 'vue'
import { PhFolderOpen, PhClockClockwise, PhStar, PhProhibit } from '@phosphor-icons/vue'

const props = defineProps({
	statusKey: { type: String, required: true },
	label: { type: String, required: true },
})

// Badge appearance per status key. `flagged` and `archived` are resolved by
// the caller; this map only translates a key into colours + icon.
const styles = {
	opened: { badge: 'bg-light-green text-green', icon: PhFolderOpen },
	extended: { badge: 'bg-light-violet text-violet', icon: PhClockClockwise },
	flagged: { badge: 'bg-light-red text-red', icon: PhStar },
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
