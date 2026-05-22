<x-auth.container>
	<x-auth.header title="Passwort zurücksetzen" description="Geben Sie Ihre E-Mail-Adresse ein und wir senden Ihnen einen Link zum Zurücksetzen." />
	<form method="POST" action="{{ route('password.email') }}" class="space-y-16">
		@csrf
		<div>
			<x-form.label for="email">E-Mail</x-form.label>
			<x-form.input type="email" name="email" :value="old('email')" required autofocus />
			<x-form.error name="email" />
		</div>
		<div class="flex items-center justify-between mt-24">
			<x-form.link :href="route('login')">Zurück zum Login</x-form.link>
			<x-form.button>Link senden</x-form.button>
		</div>
	</form>
</x-auth.container>
