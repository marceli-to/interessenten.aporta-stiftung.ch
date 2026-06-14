<script setup>
import { computed } from 'vue'
import {
	PhPencilSimple,
	PhFloppyDisk,
	PhDownloadSimple,
	PhNotePencil,
	PhFaders,
	PhPlus,
	PhTrash,
	PhDotsThree,
	PhArrowsClockwise,
	PhArrowSquareOut,
} from '@phosphor-icons/vue'

const icons = {
	'pencil-simple': PhPencilSimple,
	'floppy-disk': PhFloppyDisk,
	'download-simple': PhDownloadSimple,
	'note-pencil': PhNotePencil,
	faders: PhFaders,
	plus: PhPlus,
	trash: PhTrash,
	'dots-three': PhDotsThree,
	'arrows-clockwise': PhArrowsClockwise,
	'arrow-square-out': PhArrowSquareOut,
}

const props = defineProps({
	type: { type: String, default: 'button' },
	// Light-surface: primary | outline | ghost | danger | danger-solid
	// Dark-surface (e.g. BulkActionBar): inverse-outline | inverse-ghost
	variant: { type: String, default: 'primary' },
	size: { type: String, default: 'md' }, // sm | md | lg
	icon: { type: String, default: null },
})

const variants = {
	primary: 'border border-blue bg-blue text-white rounded-full hover:bg-blue/90',
	outline: 'border border-blue text-blue rounded-full hover:bg-light-blue',
	ghost: 'text-blue hover:opacity-70',
	danger: 'text-red hover:opacity-70',
	'danger-solid': 'border border-red bg-red text-white rounded-full hover:bg-red/90',
	// On a dark surface: white border/text outline and a white text-only ghost.
	'inverse-outline': 'border border-white/40 text-white rounded-full hover:bg-white/10',
	'inverse-ghost': 'text-white/70 underline hover:text-white',
}

// Borderless, text-only variants share the no-chrome sizing and icon scale.
const textOnly = ['ghost', 'danger', 'inverse-ghost']

const containedSizes = {
	sm: 'h-30 px-12 text-sm font-normal gap-5',
	md: 'h-40 px-20 text-md font-medium gap-10',
	lg: 'h-50 pl-20 pr-16 text-2xl font-medium gap-10',
}

const ghostSizes = {
	sm: 'text-sm font-normal gap-5',
	md: 'text-md font-medium gap-5',
	lg: 'text-2xl font-medium gap-5',
}

const sizeClasses = computed(() =>
	textOnly.includes(props.variant) ? ghostSizes[props.size] : containedSizes[props.size]
)

const iconComponent = computed(() => (props.icon ? icons[props.icon] : null))

const iconSize = computed(() => {
	if (props.size === 'lg') return 24
	if (props.size === 'md') return 18
	return 16
})
</script>

<template>
	<button
		:type="type"
		class="inline-flex items-center justify-center whitespace-nowrap cursor-pointer transition-colors focus-visible:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
		:class="[variants[variant], sizeClasses]"
	>
		<slot />
		<component :is="iconComponent" v-if="iconComponent" :size="iconSize" weight="regular" />
	</button>
</template>
