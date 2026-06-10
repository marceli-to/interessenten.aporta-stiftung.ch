<x-auth.container>
	<x-auth.header title="Interessenten-Verwaltung Anmeldung" />
	<form method="POST" action="{{ route('login') }}" class="space-y-10">
		@csrf
		<div>
			<x-form.label for="email">E-Mail</x-form.label>
			<x-form.input type="email" name="email" :value="old('email')" placeholder="beispiel@aporta-stiftung.ch" required autofocus autocomplete="username" />
			<x-form.error name="email" />
		</div>
		<div>
			<x-form.label for="password">Passwort</x-form.label>
			<x-form.input type="password" name="password" required autocomplete="current-password" />
			<x-form.error name="password" />
		</div>
		<div class="flex flex-col items-center gap-20 mt-30">
			<x-form.button>Anmelden</x-form.button>
			@if (Route::has('password.request'))
				<x-form.link :href="route('password.request')">Ich habe mein Passwort vergessen</x-form.link>
			@endif
		</div>
	</form>
</x-auth.container>
