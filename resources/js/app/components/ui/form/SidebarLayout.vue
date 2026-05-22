<template>
	<div class="flex flex-col gap-32 lg:flex-row lg:items-start">
		<div class="flex-1 min-w-0">
			<slot />
		</div>
		<aside class="w-full lg:w-280 shrink-0 border border-gray-200 bg-gray-50/50 dark:border-warm-700 dark:bg-warm-800 p-16 lg:relative lg:top-24 rounded-md">
			<slot name="sidebar" />
		</aside>
	</div>
</template>
