<script setup>
import { RouterView } from 'vue-router'
import Header from '@/components/ui/layout/Header.vue'
import Main from '@/components/ui/layout/Main.vue'
import ToastContainer from '@/components/ui/toast/Container.vue'
</script>

<template>
	<div class="min-h-full">
		<Header />
		<Main>
			<RouterView />
		</Main>
		<ToastContainer />
	</div>
</template>
