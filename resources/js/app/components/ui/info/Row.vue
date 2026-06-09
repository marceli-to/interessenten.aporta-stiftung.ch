<script setup>
defineProps({
	label: { type: String, default: null },
})
</script>

<template>
	<div class="min-h-40 grid grid-cols-12 gap-x-40 items-center">
		<div class="col-span-3">
			<label class="font-bold text-sm">
        {{ label }}
      </label>
		</div>
		<div class="col-span-9 text-md">
			<slot />
		</div>
	</div>
</template>
