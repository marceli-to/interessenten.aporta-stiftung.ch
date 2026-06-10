@props(['title', 'description' => null])

<h1 class="text-2xl font-bold text-blue {{ $description ? 'mb-15' : 'mb-25' }}">
  {{ $title }}
</h1>
@if ($description)
<p class="text-sm text-blue mb-25">
  {{ $description }}
</p>
@endif
@if (session('status'))
	<div class="mb-15 p-10 text-sm text-green font-medium bg-light-green rounded-xs border border-green">
    {{ session('status') }}
  </div>
@endif
