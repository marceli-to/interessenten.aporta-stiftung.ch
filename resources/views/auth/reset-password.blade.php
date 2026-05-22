<x-auth.container>
	<x-auth.header title="Neues Passwort" description="Legen Sie ein neues Passwort für Ihr Konto fest." />
	<form method="POST" action="{{ route('password.store') }}" class="space-y-16">
		@csrf
		<input type="hidden" name="token" value="{{ $request->route('token') }}">
		<div>
			<x-form.label for="email">E-Mail</x-form.label>
			<x-form.input type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
			<x-form.error name="email" />
		</div>
		<div>
			<x-form.label for="password">Neues Passwort</x-form.label>
			<x-form.input type="password" name="password" required autocomplete="new-password" />
			<x-form.error name="password" />
		</div>
		<div>
			<x-form.label for="password_confirmation">Passwort bestätigen</x-form.label>
			<x-form.input type="password" name="password_confirmation" required autocomplete="new-password" />
			<x-form.error name="password_confirmation" />
		</div>
		<div class="flex items-center justify-between mt-24">
			<x-form.button class="w-full">Passwort zurücksetzen</x-form.button>
		</div>
	</form>
</x-auth.container>
