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
    <script>
        // Test if JavaScript is working
        console.log('Blade template loaded');
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, waiting for Vue...');
        });
    </script>
</body>
</html>

