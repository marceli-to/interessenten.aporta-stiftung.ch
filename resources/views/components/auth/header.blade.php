@props(['title', 'description'])

<h1 class="text-xl font-bold text-blue mb-5">
  {{ $title }}
</h1>
<p class="text-md text-blue mb-25">
  {{ $description }}
</p>
@if (session('status'))
	<div class="mb-15 p-10 text-sm text-green font-medium bg-light-green rounded-xs border border-green">
    {{ session('status') }}
  </div>
@endif
