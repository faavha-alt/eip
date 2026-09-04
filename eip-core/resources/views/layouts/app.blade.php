<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#F4F4F6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EIP — Fakultas MIPA UNS')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard.js'])
    @stack('styles')
</head>
<body class="h-full flex overflow-hidden text-slate-900 bg-[#F4F4F6] selection:bg-indigo-100 selection:text-indigo-900">

    @include('layouts.sidebar')

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden pr-0 lg:pr-5 py-0 lg:py-5">
        @include('layouts.header')

        <main class="flex-1 overflow-y-auto custom-scroll space-y-5 pr-1 px-4 lg:px-0 pb-5">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
