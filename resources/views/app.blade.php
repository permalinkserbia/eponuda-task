<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div id="app">
        <!-- Vue will mount here -->
        <div style="padding: 20px; text-align: center;">
            <p>If you see this, Vue hasn't mounted yet.</p>
            <p>Check the browser console (F12) for errors.</p>
        </div>
    </div>
</body>
</html>

