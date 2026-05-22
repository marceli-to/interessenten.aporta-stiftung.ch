<x-auth.container>
	<x-auth.header title="Anmelden" description="Melden Sie sich mit Ihrem Konto an." />
	<form method="POST" action="{{ route('login') }}" class="space-y-16">
		@csrf
		<div>
			<x-form.label for="email">E-Mail</x-form.label>
			<x-form.input type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
			<x-form.error name="email" />
		</div>
		<div>
			<x-form.label for="password">Passwort</x-form.label>
			<x-form.input type="password" name="password" required autocomplete="current-password" />
			<x-form.error name="password" />
		</div>
		<div class="flex items-center justify-between mt-24">
			@if (Route::has('password.request'))
				<x-form.link :href="route('password.request')">Passwort vergessen?</x-form.link>
			@endif
			<x-form.button>Anmelden</x-form.button>
		</div>
	</form>
</x-auth.container>
