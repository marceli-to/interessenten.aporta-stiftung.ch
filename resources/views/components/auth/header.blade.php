@props(['title', 'description'])

<h1 class="text-lg font-medium text-blue mb-4">{{ $title }}</h1>
<p class="text-sm text-blue mb-24">{{ $description }}</p>
@if (session('status'))
	<div class="mb-16 p-12 text-sm text-emerald-700 bg-emerald-50 rounded-md border border-emerald-200">{{ session('status') }}</div>
@endif
