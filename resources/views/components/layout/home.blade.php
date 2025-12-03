<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="esp-id" content="{{ session('current_esp_id') }}">

    <title>{{ $title }}</title>

    @stack('css')
    <x-part.css />

</head>

<body>
    <div id="app">

        <x-part.sidebar />
        <div id="main">

            <x-part.navbar />

            {{ $slot }}

            <x-part.footer />

        </div>
    </div>

    @stack('js')
    <x-part.js />

</body>

</html>
