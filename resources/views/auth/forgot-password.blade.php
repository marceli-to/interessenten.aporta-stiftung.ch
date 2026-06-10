<x-auth.container>
	<x-auth.header title="Passwort zurücksetzen" description="Geben Sie Ihre E-Mail-Adresse ein und wir senden Ihnen einen Link zum Zurücksetzen." />
	<form method="POST" action="{{ route('password.email') }}" class="space-y-10">
		@csrf
		<div>
			<x-form.label for="email">E-Mail</x-form.label>
			<x-form.input type="email" name="email" :value="old('email')" placeholder="beispiel@aporta-stiftung.ch" required autofocus />
			<x-form.error name="email" />
		</div>
		<div class="flex flex-col items-center gap-20 mt-30">
			<x-form.button>Link senden</x-form.button>
			<x-form.link :href="route('login')">Zurück zum Login</x-form.link>
		</div>
	</form>
</x-auth.container>
