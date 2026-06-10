<script setup>
// A selectable button styled like a filter Input: icon + label on the left,
// count on the right. `active` fills it blue. The native click falls through.
defineProps({
	icon: { type: [Object, Function], required: true },
	label: { type: String, required: true },
	count: { type: [Number, String], default: null },
	active: { type: Boolean, default: false },
})
</script>

<template>
	<button
		type="button"
		class="flex items-center gap-10 w-full min-h-32 px-10 border border-blue rounded-xs text-sm transition-colors cursor-pointer"
		:class="active ? 'bg-blue text-white' : 'bg-light-blue text-blue hover:bg-light-blue'"
	>
		<component :is="icon" :size="18" weight="regular" />
		<span class="font-medium">
      {{ label }}
    </span>
		<span class="ml-auto" :class="active ? 'text-white/80' : 'text-blue/50'">
			{{ count }}
		</span>
	</button>
</template>
