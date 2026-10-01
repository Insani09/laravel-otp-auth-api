<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sistem Manajemen Pengguna &amp; Wilayah</title>
    <style>
        /* Shell loading state — tampil sebelum Vue mount, menghindari kedip putih. */
        #vue-app:not(:has(*)) { min-height: 100vh; }
        .app-boot {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #020617;
            color: #94a3b8;
            font-family: ui-sans-serif, system-ui, sans-serif;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="vue-app">
        <div class="app-boot">Memuat aplikasi...</div>
    </div>
    <noscript>
        <div style="padding:2rem;text-align:center;font-family:sans-serif">
            Aplikasi ini membutuhkan JavaScript. Silakan aktifkan JavaScript di browser Anda.
        </div>
    </noscript>
</body>
</html>
