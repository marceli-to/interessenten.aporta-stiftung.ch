<!DOCTYPE html>
<html lang="de" class="h-full scroll-smooth">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ config('app.name') }}</title>
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="/favicon.svg" />
<link rel="shortcut icon" href="/favicon.ico" />
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
<meta name="apple-mobile-web-app-title" content="Interessenten Aporta Stiftung" />
<link rel="manifest" href="/site.webmanifest" />
@vite(['resources/css/app.css'])
</head>
<body class="h-full text-sm font-sans tracking-wide text-gray-900 antialiased bg-gray-100">
  {{ $slot }}
</body>
</html>
