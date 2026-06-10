<x-layout.guest>
	<div class="min-h-dvh flex items-center justify-center px-20 py-50 bg-light-blue bg-no-repeat bg-cover bg-center bg-pattern">
		<div class="w-full max-w-md bg-white rounded-2xl shadow-xl px-20 py-25">
			<div class="flex justify-center text-blue">
				<x-icons.logo class="w-125" />
			</div>
			<hr class="border-blue/30 my-20">
      <div class="px-30">
			  {{ $slot }}
      </div>
		</div>
	</div>
</x-layout.guest>
