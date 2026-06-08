<script setup>
import { RouterView } from 'vue-router'
import Header from '@/components/ui/layout/Header.vue'
import Main from '@/components/ui/layout/Main.vue'
import ToastContainer from '@/components/ui/toast/Container.vue'
</script>

<template>
	<div class="max-w-[90rem] mx-auto px-25 flex flex-col min-h-screen">
		<Header />
		<Main class="pb-25 flex-1">
			<RouterView />
		</Main>
		<ToastContainer />
	</div>
</template>
