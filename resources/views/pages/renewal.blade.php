<x-auth.container>
	@switch($state)
		@case('confirm')
			<x-auth.header title="Bewerbung verlängern" />
			<div class="space-y-15 text-lg text-blue text-pretty">
				<p>Sind Ihre Angaben noch aktuell, können Sie sie direkt bestätigen.</p>
			</div>
			<form method="POST" action="{{ route('renewal.confirm.store', $token) }}" class="flex flex-col gap-15 mt-30">
				@csrf
				<x-form.button class="w-full">Angaben bestätigen</x-form.button>
				<x-form.button variant="outline" :href="$updateUrl" class="w-full">Angaben zuerst aktualisieren</x-form.button>
			</form>
			@break

		@case('confirmed')
			<x-auth.header title="Vielen Dank" />
			<div class="space-y-15 text-lg text-blue text-pretty">
				<p>Ihre Wohnungsbewerbung wurde um weitere 6 Monate verlängert und ist bis am {{ $valid_until }} gültig.</p>
				<p>Sollten Sie danach weiterhin eine Wohnung suchen, melden Sie sich bitte wieder per E-Mail an <a href="mailto:wohnung@aporta-stiftung.ch" class="underline">wohnung@aporta-stiftung.ch</a> oder telefonisch bei uns.</p>
			</div>
			@break

		@default
			<x-auth.header title="Link nicht mehr gültig" />
			<div class="space-y-15 text-lg text-blue text-pretty">
				<p>Dieser Link ist abgelaufen oder wurde bereits verwendet.</p>
				<p>Möchten Sie Ihre Bewerbung verlängern oder haben Sie Fragen dazu? Schreiben Sie uns bitte an <a href="mailto:wohnung@aporta-stiftung.ch" class="underline">wohnung@aporta-stiftung.ch</a>, wir senden Ihnen gerne einen neuen Link.</p>
			</div>
	@endswitch
</x-auth.container>
