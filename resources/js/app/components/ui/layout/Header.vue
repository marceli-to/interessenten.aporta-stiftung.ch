<script setup>
import { RouterLink } from 'vue-router'
import Logo from '@/components/ui/icons/Logo.vue'
import MenuContainer from '@/components/ui/layout/menu/Container.vue'
import MenuItem from '@/components/ui/layout/menu/Item.vue'

const currentUser = { name: 'Marcel Stadelmann' }
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? ''
</script>

<template>
	<header class="bg-white border-b border-gray-200">
		<div class="max-w-7xl mx-auto px-24 py-16 h-auto flex items-center gap-48">

			<RouterLink :to="{ name: 'applications.index' }" class="text-blue">
				<Logo class="h-36 w-auto" />
			</RouterLink>

			<MenuContainer>
				<MenuItem :to="{ name: 'applications.index' }">
          Bewerbungen
        </MenuItem>
				<MenuItem :to="{ name: 'users.index' }">
          Benutzer
        </MenuItem>
			</MenuContainer>

			<div class="ml-auto flex items-center gap-16 text-sm">
				<span class="text-gray-600">
          {{ currentUser.name }}
        </span>
				<form method="POST" action="/logout">
					<input type="hidden" name="_token" :value="csrfToken" />
					<button type="submit" class="text-gray-500 hover:text-blue cursor-pointer">
            Logout
          </button>
				</form>
			</div>

		</div>
	</header>
</template>
