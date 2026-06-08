<script setup>
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import Logo from '@/components/ui/icons/Logo.vue'
import User from '@/components/ui/icons/User.vue'
import { useUserStore } from '@/stores/user'

const userStore = useUserStore()

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? ''

onMounted(() => userStore.fetchUser())
</script>

<template>

	<header class="bg-light-blue border-b border-b-blue/30 py-30 mb-25 w-full sticky top-0 z-10">

    <div class="grid grid-cols-12 items-center">

      <div class="col-span-4">
        <h1 class="text-2xl font-bold leading-none">
          Interessenten-Verwaltung
        </h1>
      </div>

      <div class="col-span-4 flex justify-center">
        <RouterLink :to="{ name: 'applications.index' }">
          <Logo class="h-36 w-auto" />
        </RouterLink>
      </div>

      <div class="col-span-4 flex gap-15 items-center justify-end">

				<form 
          method="POST" 
          action="/logout"
          class="group"
          v-if="userStore.user">
					<input type="hidden" name="_token" :value="csrfToken" />
					<button 
            type="submit" 
            class="cursor-pointer"
            aria-label="Abmelden">
            <span class="flex items-center gap-10 group-hover:underline underline-offset-2 decoration-1">
              {{ userStore.user.full_name }}
              <span class="bg-blue rounded-full text-white h-35 w-35 flex items-center justify-center">
                {{ userStore.user.initials }}
              </span>
            </span>
          </button>
				</form>

        <RouterLink 
          :to="{ name: 'users.index' }" 
          class="w-24 h-24 flex items-center justify-center"
          aria-label="Benutzerverwaltung">
          <User class="text-blue w-22 h-auto" />
        </RouterLink>

      </div>

    </div>

	</header>

</template>
