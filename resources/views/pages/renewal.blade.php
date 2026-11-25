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
			<p class="mt-30 text-md text-blue text-center text-balance">Suchen Sie keine Wohnung mehr? <a href="{{ route('renewal.delete', $token) }}" class="underline">Bewerbung löschen</a></p>
			@break

		@case('delete')
			<x-auth.header title="Bewerbung löschen" />
			<div class="space-y-15 text-lg text-blue text-pretty">
				<p>Möchten Sie Ihre Wohnungsbewerbung wirklich löschen?</p>
				<p>Alle Ihre Angaben werden somit endgültig gelöscht.</p>
			</div>
			<form method="POST" action="{{ route('renewal.delete.store', $token) }}" class="flex flex-col items-center gap-20 mt-30">
				@csrf
				<x-form.button>Bewerbung endgültig löschen</x-form.button>
				<x-form.link :href="route('renewal.confirm', $token)">Nein, Bewerbung behalten</x-form.link>
			</form>
			@break

		@case('confirmed')
			<x-auth.header title="Vielen Dank" />
			<div class="space-y-15 text-lg text-blue text-pretty">
				<p>Ihre Wohnungsbewerbung wurde um weitere 6 Monate verlängert und ist bis am {{ $valid_until }} gültig.</p>
				<p>Sie erhalten eine Bestätigung per E-Mail.</p>
			</div>
			@break

		@case('deleted')
			<x-auth.header title="Bewerbung gelöscht" />
			<p class="text-lg text-blue text-pretty">Ihre Wohnungsbewerbung und alle dazugehörigen Angaben wurden gelöscht.</p>
			@break

		@default
			<x-auth.header title="Link nicht mehr gültig" />
			<div class="space-y-15 text-lg text-blue text-pretty">
				<p>Dieser Link ist abgelaufen oder wurde bereits verwendet.</p>
				<p>Möchten Sie Ihre Bewerbung verlängern oder haben Sie Fragen dazu? Schreiben Sie uns bitte an <a href="mailto:wohnung@aporta-stiftung.ch" class="underline">wohnung@aporta-stiftung.ch</a>, wir senden Ihnen gerne einen neuen Link.</p>
			</div>
	@endswitch
</x-auth.container>
