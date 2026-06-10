<script setup>
defineProps({
	label: { type: String, default: null },
})
</script>

<template>
	<div class="min-h-40 py-3 grid grid-cols-12 gap-x-40 items-center">
		<div class="col-span-4">
			<slot name="label">
				<label class="font-bold text-sm">
          {{ label }}
        </label>
			</slot>
		</div>
		<div class="col-span-8 text-md">
      <slot />
		</div>
	</div>
</template>
