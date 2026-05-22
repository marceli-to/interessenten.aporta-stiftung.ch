<x-layout.guest>
	<div class="min-h-dvh flex">
		<div class="hidden lg:block lg:w-1/2 relative">
			<img src="{{ asset('img/aporta-splash.jpg') }}" alt="" class="absolute inset-0 w-full h-full object-cover" />
			<div class="absolute inset-0 bg-black/20"></div>
			<div class="relative z-10 flex items-end p-48 h-full text-white">
				<x-icons.logo class="w-212 text-white" />
			</div>
		</div>
		<div class="w-full lg:w-1/2 bg-white dark:bg-warm-900 flex items-center justify-center px-32 py-48">
			<div class="w-full max-w-sm">
				<div class="lg:hidden mb-32 text-gray-900 dark:text-warm-100">
					<x-icons.logo class="w-120 text-accent" />
				</div>
				{{ $slot }}
			</div>
		</div>
	</div>
</x-layout.guest>
