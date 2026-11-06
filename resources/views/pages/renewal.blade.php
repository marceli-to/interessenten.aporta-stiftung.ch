<x-auth.container>
	@switch($state)
		@case('confirm')
			<x-auth.header title="Bewerbung verlängern" />
			<div class="space-y-15 text-sm text-blue">
				<p>Ihre Wohnungsbewerbung Nr. {{ $referenceNumber }} ist bis am {{ $validUntil }} gültig.</p>
				<p>Mit der Bestätigung bleiben Ihre Angaben unverändert und Ihre Bewerbung wird um weitere 6 Monate verlängert.</p>
			</div>
			<form method="POST" action="{{ route('renewal.confirm.store', $token) }}" class="flex flex-col items-center gap-20 mt-30">
				@csrf
				<x-form.button>Angaben bestätigen</x-form.button>
				<x-form.link :href="$updateUrl">Angaben zuerst aktualisieren</x-form.link>
			</form>
			@break

		@case('delete')
			<x-auth.header title="Bewerbung löschen" />
			<div class="space-y-15 text-sm text-blue">
				<p>Möchten Sie Ihre Wohnungsbewerbung Nr. {{ $referenceNumber }} wirklich löschen?</p>
				<p>Alle Ihre Angaben werden endgültig gelöscht. Das kann nicht rückgängig gemacht werden.</p>
			</div>
			<form method="POST" action="{{ route('renewal.delete.store', $token) }}" class="flex flex-col items-center gap-20 mt-30">
				@csrf
				<x-form.button>Bewerbung endgültig löschen</x-form.button>
				<x-form.link :href="route('renewal.confirm', $token)">Nein, Bewerbung behalten</x-form.link>
			</form>
			@break

		@case('confirmed')
			<x-auth.header title="Vielen Dank" />
			<p class="text-sm text-blue">Ihre Wohnungsbewerbung wurde verlängert und ist bis am {{ $valid_until }} gültig. Sie erhalten eine Bestätigung per E-Mail.</p>
			@break

		@case('deleted')
			<x-auth.header title="Bewerbung gelöscht" />
			<p class="text-sm text-blue">Ihre Wohnungsbewerbung und alle dazugehörigen Angaben wurden gelöscht.</p>
			@break

		@default
			<x-auth.header title="Link nicht mehr gültig" />
			<div class="space-y-15 text-sm text-blue">
				<p>Dieser Link ist abgelaufen oder wurde bereits verwendet.</p>
				<p>Möchten Sie Ihre Bewerbung verlängern oder haben Sie Fragen dazu? Schreiben Sie uns bitte an <a href="mailto:wohnung@aporta-stiftung.ch" class="underline">wohnung@aporta-stiftung.ch</a>, wir senden Ihnen gerne einen neuen Link.</p>
			</div>
	@endswitch
</x-auth.container>
